@php
    $steps = [
        ['Booked', 'Booking created', true],
        ['Paid into escrow', 'Payment verified and held safely', in_array($job->payment_status, ['paid', 'released'])],
        ['Maid assigned', $job->assignedMaid?->name ?? 'A verified maid claims the gig', $job->maid_id !== null],
        ['In progress', 'Cleaning under way', $job->started_at !== null || $job->completed_at !== null],
        ['Completed', 'Maid marked the job done', $job->completed_at !== null],
        ['Approved & paid out', 'Escrow released to the maid', $job->client_approved_at !== null],
    ];
    $currentIndex = collect($steps)->search(fn ($step) => ! $step[2]);
@endphp

@if ($job->isCancelled())
    <div class="alert alert--error">This booking was cancelled{{ $job->cancelled_at ? ' on '.$job->cancelled_at->format('M j, Y') : '' }}.</div>
@else
    <ol class="tracker">
        @foreach ($steps as $index => [$label, $hint, $done])
            <li class="tracker__step {{ $done ? 'is-done' : '' }} {{ $index === $currentIndex ? 'is-current' : '' }}">
                <span class="tracker__dot">{{ $done ? '✓' : $index + 1 }}</span>
                <div>
                    <div class="tracker__label">{{ $label }}</div>
                    <div class="tracker__hint">{{ $hint }}</div>
                </div>
            </li>
        @endforeach
    </ol>
@endif
