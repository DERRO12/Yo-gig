<article class="card service-card">
    <div class="service-card__img">
        <img src="{{ $service->imageUrl() }}" alt="{{ $service->name }}" loading="lazy">
    </div>
    <div class="service-card__body">
        @if ($service->category)
            <span class="feature-card__label">{{ $service->category }}</span>
        @endif
        <h3>{{ $service->name }}</h3>
        <p>{{ $service->short_description }}</p>
        <div class="service-card__foot">
            <div>
                @if ($service->hasFixedPrice())
                    <div class="price">{{ $site->money($service->base_price) }} <small>{{ $service->price_unit }}</small></div>
                @else
                    <div class="price">Custom quote</div>
                @endif
                @if ($service->price_label)
                    <span class="small text-muted">{{ $service->price_label }}</span>
                @endif
            </div>
            <a href="{{ auth()->check() && auth()->user()->isClient() ? route('client.bookings.create', ['service' => $service->id]) : route('register') }}" class="btn btn-primary btn-sm">Book now</a>
        </div>
    </div>
</article>
