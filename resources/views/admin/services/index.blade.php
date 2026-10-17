@extends('layouts.admin')

@section('title', 'Services & pricing')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Services & pricing</h1>
            <p>These power the Services page, the homepage highlights and the booking form prices.</p>
        </div>
        <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm">+ Add service</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr><th></th><th>Service</th><th>Price</th><th>Bookings</th><th>Visibility</th><th></th></tr></thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td data-label=""><img src="{{ $service->imageUrl() }}" alt="" class="thumb"></td>
                            <td data-label="Service">
                                <strong>{{ $service->name }}</strong>
                                <div class="small text-muted">{{ $service->category }} · order {{ $service->sort_order }}</div>
                            </td>
                            <td data-label="Price" class="nowrap">{{ $service->hasFixedPrice() ? $site->money($service->base_price).' '.$service->price_unit : 'Custom quote' }}</td>
                            <td data-label="Bookings">{{ $service->jobs_count }}</td>
                            <td data-label="Visibility">
                                <span class="badge {{ $service->is_active ? 'badge--green' : 'badge--red' }}">{{ $service->is_active ? 'Live' : 'Hidden' }}</span>
                                @if ($service->is_featured)<span class="badge badge--gold">Featured</span>@endif
                            </td>
                            <td data-label="">
                                <div class="inline-actions">
                                    <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-ghost btn-sm">Edit</a>
                                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" data-confirm="Delete {{ $service->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outline btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No services yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
