<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Support\BookingWorkflow;
use App\Support\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(public BookingWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $bookings = Job::query()
            ->with(['client', 'assignedMaid', 'service'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('payment'), fn ($query) => $query->where('payment_status', $request->string('payment')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('reference', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', ['bookings' => $bookings]);
    }

    public function show(Job $job): View
    {
        $job->load(['client', 'assignedMaid.maidProfile', 'service', 'payments.verifier', 'payout']);

        return view('admin.bookings.show', [
            'job' => $job,
            'maids' => User::query()
                ->where('role', 'maid')
                ->where('is_active', true)
                ->whereHas('maidProfile', fn ($q) => $q->where('verification_status', 'verified'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function assign(Request $request, Job $job): RedirectResponse
    {
        $validated = $request->validate([
            'maid_id' => ['required', Rule::exists('users', 'id')->where('role', 'maid')],
        ]);

        if (! in_array($job->status, [Job::STATUS_OPEN, Job::STATUS_MATCHED], true)) {
            return back()->with('error', 'Maids can only be assigned to open or matched bookings.');
        }

        $this->workflow->assignMaid($job, User::query()->findOrFail($validated['maid_id']));

        return back()->with('success', 'Maid assigned to booking.');
    }

    /**
     * Admin overrides for disputes and support: move a booking through the escrow flow.
     */
    public function updateStatus(Request $request, Job $job): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['start', 'complete', 'approve', 'cancel', 'refund', 'unassign'])],
        ]);

        [$allowed, $error] = match ($validated['action']) {
            'start' => [$job->isMatched(), 'Only matched bookings can be started.'],
            'complete' => [$job->isInProgress(), 'Only bookings in progress can be completed.'],
            'approve' => [$job->isAwaitingClientApproval(), 'Only completed bookings awaiting approval can be released.'],
            'cancel' => [! $job->isCompleted() && ! $job->isCancelled(), 'This booking cannot be cancelled.'],
            'refund' => [$job->payment_status === Job::PAYMENT_REFUND_DUE, 'This booking has no refund due.'],
            'unassign' => [$job->isMatched(), 'Only matched bookings can be unassigned.'],
        };

        if (! $allowed) {
            return back()->with('error', $error);
        }

        match ($validated['action']) {
            'start' => $this->workflow->start($job),
            'complete' => $this->workflow->complete($job),
            'approve' => $this->workflow->approve($job),
            'cancel' => $this->workflow->cancel($job),
            'refund' => $this->workflow->markRefunded($job),
            'unassign' => $this->workflow->releaseMaid($job),
        };

        return back()->with('success', 'Booking updated.');
    }

    public function destroy(Job $job): RedirectResponse
    {
        foreach ($job->images ?? [] as $path) {
            Media::delete($path);
        }

        $job->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted.');
    }
}
