@extends('layouts.app')

@section('title', 'Book a Maid')

@section('content')
    <div class="container dash container-narrow">
        <div class="dash-header">
            <div>
                <a href="{{ route('client.dashboard') }}" class="small">← Back to my bookings</a>
                <h1>Book a maid</h1>
                <p>Choose a service, tell us when and where, then pay securely into escrow.</p>
            </div>
        </div>

        <form action="{{ route('client.bookings.store') }}" method="POST" enctype="multipart/form-data" class="card card-body" data-booking-form data-currency="{{ $site->currency() }}">
            @csrf

            <h2 class="card-title mb-2">1. Choose a service</h2>
            @if ($services->isEmpty())
                <div class="alert alert--warning">No services are available right now. Please contact support.</div>
            @endif
            <div class="choice-grid mb-3">
                @foreach ($services as $service)
                    <label class="choice">
                        <input type="radio" name="service_id" value="{{ $service->id }}" data-price="{{ (float) $service->base_price }}" data-unit="{{ $service->price_unit }}"
                            @checked((int) old('service_id', $selectedServiceId ?? ($loop->first ? $service->id : null)) === $service->id) required>
                        <img src="{{ $service->imageUrl() }}" alt="">
                        <span>
                            <span class="choice__name">{{ $service->name }}</span><br>
                            <span class="choice__price">{{ $service->hasFixedPrice() ? $site->money($service->base_price).' '.$service->price_unit : 'Custom quote' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity <span class="text-muted" data-unit-label></span></label>
                    <input type="number" id="quantity" name="quantity" class="input" min="1" max="50" value="{{ old('quantity', 1) }}" required>
                    <span class="form-hint">Rooms, hours or visits depending on the service.</span>
                </div>
                <div class="form-group" data-budget-group hidden>
                    <label for="budget">Your budget ({{ $site->currency() }})</label>
                    <input type="number" id="budget" name="budget" class="input" min="0" step="500" value="{{ old('budget') }}">
                    <span class="form-hint">For custom-quote services, offer what you will pay.</span>
                </div>
            </div>

            <h2 class="card-title mb-2 mt-2">2. When and where</h2>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" class="input" value="{{ old('location') }}" placeholder="e.g. Plot 5, Ntinda, Kampala" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="scheduled_date">Date</label>
                    <input type="date" id="scheduled_date" name="scheduled_date" class="input" value="{{ old('scheduled_date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label for="scheduled_time">Time</label>
                    <input type="time" id="scheduled_time" name="scheduled_time" class="input" value="{{ old('scheduled_time', '09:00') }}" required>
                </div>
            </div>

            <h2 class="card-title mb-2 mt-2">3. Details for the maid</h2>
            <div class="form-group">
                <label for="title">Short title (optional)</label>
                <input type="text" id="title" name="title" class="input" value="{{ old('title') }}" placeholder="e.g. Move-out clean, 3 bedroom house">
            </div>
            <div class="form-group">
                <label for="description">Instructions</label>
                <textarea id="description" name="description" class="input" rows="4" placeholder="Tasks, rooms, supplies, pets, gate instructions...">{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
                <label for="images">Photos of the space (optional, up to 5)</label>
                <input type="file" id="images" name="images[]" class="input" accept="image/jpeg,image/png,image/webp" multiple data-preview="booking-preview">
                <span class="form-hint">Helps maids understand the job. Max 5 MB each.</span>
                <div class="image-preview" id="booking-preview"></div>
            </div>

            <div class="price-summary">
                <span>Total to pay into escrow</span>
                <strong data-total>{{ $site->money(0) }}</strong>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Continue to payment</button>
            <p class="small text-muted text-center mt-1">🔒 The maid is only paid after you approve the finished work.</p>
        </form>
    </div>
@endsection
