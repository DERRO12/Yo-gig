@props(['status'])

@php
    $tone = match ($status) {
        'pending' => 'amber',
        'successful', 'paid', 'verified' => 'green',
        'failed', 'rejected' => 'red',
        'refunded' => 'purple',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge badge--{$tone}"]) }}>{{ ucfirst($status) }}</span>
