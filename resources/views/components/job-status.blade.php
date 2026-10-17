@props(['job'])

@php
    $tone = match (true) {
        $job->isAwaitingClientApproval() => 'gold',
        $job->status === 'open' => 'amber',
        $job->status === 'matched' => 'blue',
        $job->status === 'in_progress' => 'purple',
        $job->status === 'completed' => 'green',
        $job->status === 'cancelled' => 'red',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge badge--{$tone}"]) }}>{{ $job->statusLabel() }}</span>
