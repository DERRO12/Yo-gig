<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    /**
     * Display a listing of the client's jobs.
     */
    public function index()
    {
        $jobs = Job::where('client_id', Auth::id())
            ->with('assignedMaid')
            ->latest()
            ->get();

        return view('client.jobs.index', compact('jobs'));
    }

    /**
     * Show the form for creating a new job.
     */
    public function create()
    {
        return view('client.jobs.create');
    }

    /**
     * Store a newly created job in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'location'       => 'required|string|max:255',
            'budget'         => 'nullable|numeric|min:0',
            'description'    => 'nullable|string|max:2000',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('job_images', 'public');
            }
        }

        Job::create([
            'client_id'      => Auth::id(),
            'title'          => $validated['title'],
            'location'       => $validated['location'],
            'budget'         => $validated['budget'] ?? null,
            'description'    => $validated['description'] ?? null,
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'],
            'images'         => $imagePaths,
            'status'         => 'open',
        ]);

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Your cleaning job has been posted successfully!');
    }

    /**
     * Display the specified job.
     */
    public function show(Job $job)
    {
        if ($job->client_id !== Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        $job->load('assignedMaid');

        return view('client.jobs.show', compact('job'));
    }

    /**
     * Show the form for editing the specified job.
     */
    public function edit(Job $job)
    {
        if ($job->client_id !== Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        return view('client.jobs.edit', compact('job'));
    }

    /**
     * Update the specified job in storage.
     */
    public function update(Request $request, Job $job)
    {
        if ($job->client_id !== Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'location'       => 'required|string|max:255',
            'budget'         => 'nullable|numeric|min:0',
            'description'    => 'nullable|string|max:2000',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'images'         => 'nullable|array|max:5',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Handle new image uploads (append to existing)
        $imagePaths = $job->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('job_images', 'public');
            }
        }

        $job->update([
            'title'          => $validated['title'],
            'location'       => $validated['location'],
            'budget'         => $validated['budget'] ?? null,
            'description'    => $validated['description'] ?? null,
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'],
            'images'         => $imagePaths,
        ]);

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Job updated successfully!');
    }

    /**
     * Remove the specified job from storage.
     */
    public function destroy(Job $job)
    {
        if ($job->client_id !== Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        if (in_array($job->status, ['in_progress', 'completed'])) {
            return back()->with('error', 'You cannot delete a job that is already in progress or completed.');
        }

        // Delete associated images from storage
        if ($job->images && is_array($job->images)) {
            foreach ($job->images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $job->delete();

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Job deleted successfully.');
    }
}