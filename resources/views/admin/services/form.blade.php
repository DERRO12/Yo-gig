@extends('layouts.admin')

@section('title', $service->exists ? 'Edit service' : 'New service')

@section('content')
    <div class="dash-header">
        <div>
            <a href="{{ route('admin.services.index') }}" class="small">← Services</a>
            <h1>{{ $service->exists ? 'Edit '.$service->name : 'New service' }}</h1>
        </div>
    </div>

    <form action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" method="POST" enctype="multipart/form-data" class="card card-body" style="max-width: 820px;">
        @csrf
        @if ($service->exists) @method('PUT') @endif

        <div class="form-row">
            <div class="form-group">
                <label for="name">Service name</label>
                <input type="text" id="name" name="name" class="input" value="{{ old('name', $service->name) }}" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" class="input" value="{{ old('category', $service->category) }}" placeholder="Residential, Commercial...">
            </div>
        </div>

        <div class="form-group">
            <label for="short_description">Short description</label>
            <textarea id="short_description" name="short_description" class="input" rows="3">{{ old('short_description', $service->short_description) }}</textarea>
            <span class="form-hint">Shown on service cards.</span>
        </div>

        <div class="form-group">
            <label for="description">Full description (optional)</label>
            <textarea id="description" name="description" class="input" rows="4">{{ old('description', $service->description) }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="base_price">Price ({{ $site->currency() }})</label>
                <input type="number" id="base_price" name="base_price" class="input" min="0" step="500" value="{{ old('base_price', (float) $service->base_price) }}" required>
                <span class="form-hint">Set 0 for custom-quote services (client enters a budget).</span>
            </div>
            <div class="form-group">
                <label for="price_unit">Priced</label>
                <input type="text" id="price_unit" name="price_unit" class="input" value="{{ old('price_unit', $service->price_unit) }}" list="price-units" required>
                <datalist id="price-units">
                    <option value="per visit"><option value="per room"><option value="per hour"><option value="per basket"><option value="per square metre">
                </datalist>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price_label">Price note (optional)</label>
                <input type="text" id="price_label" name="price_label" class="input" value="{{ old('price_label', $service->price_label) }}" placeholder="e.g. Flexible contracts">
            </div>
            <div class="form-group">
                <label for="sort_order">Display order</label>
                <input type="number" id="sort_order" name="sort_order" class="input" min="0" value="{{ old('sort_order', $service->sort_order ?? 0) }}">
            </div>
        </div>

        @include('admin.partials.image-field', ['name' => 'image', 'label' => 'Image', 'currentUrl' => $service->image_path ? $service->imageUrl() : null])

        <div class="form-group inline-actions">
            <label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))> Visible on the website</label>
            <label class="checkbox"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $service->is_featured))> Feature on homepage</label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $service->exists ? 'Save changes' : 'Create service' }}</button>
            <a href="{{ route('admin.services.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
@endsection
