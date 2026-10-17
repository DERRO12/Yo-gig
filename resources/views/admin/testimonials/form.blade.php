@extends('layouts.admin')

@section('title', $testimonial->exists ? 'Edit testimonial' : 'New testimonial')

@section('content')
    <div class="dash-header">
        <div>
            <a href="{{ route('admin.testimonials.index') }}" class="small">← Testimonials</a>
            <h1>{{ $testimonial->exists ? 'Edit testimonial' : 'New testimonial' }}</h1>
        </div>
    </div>

    <form action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" method="POST" enctype="multipart/form-data" class="card card-body" style="max-width: 720px;">
        @csrf
        @if ($testimonial->exists) @method('PUT') @endif

        <div class="form-row">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" class="input" value="{{ old('name', $testimonial->name) }}" required>
            </div>
            <div class="form-group">
                <label for="role">Role or location</label>
                <input type="text" id="role" name="role" class="input" value="{{ old('role', $testimonial->role) }}" placeholder="Client, Ntinda">
            </div>
        </div>
        <div class="form-group">
            <label for="quote">Quote</label>
            <textarea id="quote" name="quote" class="input" rows="4" required>{{ old('quote', $testimonial->quote) }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="rating">Rating</label>
                <select id="rating" name="rating" class="input">
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((int) old('rating', $testimonial->rating) === $i)>{{ str_repeat('★', $i) }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group">
                <label for="sort_order">Display order</label>
                <input type="number" id="sort_order" name="sort_order" class="input" min="0" value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}">
            </div>
        </div>

        @include('admin.partials.image-field', ['name' => 'photo', 'label' => 'Photo (optional)', 'currentUrl' => $testimonial->photoUrl(), 'hint' => 'Square images work best. Up to 2 MB.'])

        <div class="form-group">
            <label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $testimonial->is_active))> Show on the website</label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $testimonial->exists ? 'Save changes' : 'Create testimonial' }}</button>
            <a href="{{ route('admin.testimonials.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
@endsection
