@extends('layouts.admin')

@section('title', 'Testimonials')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Testimonials</h1>
            <p>Shown on the homepage.</p>
        </div>
        <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary btn-sm">+ Add testimonial</a>
    </div>

    <div class="card">
        @forelse ($testimonials as $testimonial)
            <div class="content-item">
                <div>
                    @if ($testimonial->photoUrl())
                        <img src="{{ $testimonial->photoUrl() }}" alt="" class="thumb" style="width: 48px; height: 48px; border-radius: 50%;">
                    @else
                        <span class="avatar">{{ strtoupper(substr($testimonial->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="content-item__body">
                    <h4>{{ $testimonial->name }} <span class="small text-muted">{{ $testimonial->role }}</span>
                        <span style="color: var(--accent);">{{ str_repeat('★', $testimonial->rating) }}</span>
                        @unless ($testimonial->is_active)<span class="badge badge--red">Hidden</span>@endunless
                    </h4>
                    <p>“{{ Str::limit($testimonial->quote, 160) }}”</p>
                </div>
                <div class="inline-actions">
                    <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('admin.testimonials.destroy', $testimonial) }}" method="POST" data-confirm="Delete this testimonial?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger-outline btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty"><p>No testimonials yet.</p></div>
        @endforelse
    </div>
@endsection
