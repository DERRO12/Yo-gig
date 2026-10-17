@props(['job'])

@php
    $tone = match ($job->payment_status) {
        'unpaid' => 'red',
        'pending' => 'amber',
        'paid' => 'teal',
        'released' => 'green',
        'refund_due' => 'purple',
        'refunded' => '',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge badge--{$tone}"]) }}>💳 {{ $job->paymentStatusLabel() }}</span>
