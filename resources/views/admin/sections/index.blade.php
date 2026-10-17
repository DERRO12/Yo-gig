@extends('layouts.admin')

@section('title', 'Page content')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Page content</h1>
            <p>Edit the headings, text, images and buttons shown on each public page.</p>
        </div>
        <a href="{{ route($page === 'home' ? 'landing' : $page) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">↗ View {{ ucfirst($page) }} page</a>
    </div>

    <nav class="tabs">
        @foreach ($pages as $key => $sectionList)
            <a href="{{ route('admin.sections.index', ['page' => $key]) }}" class="{{ $page === $key ? 'is-active' : '' }}">{{ ucfirst($key) }}</a>
        @endforeach
    </nav>

    @foreach ($pages[$page] as $sectionKey => $sectionLabel)
        @php($blocks = $sections->get($sectionKey, collect()))
        <div class="section-block">
            <div class="section-block__head">
                <h2 class="card-title">{{ $sectionLabel }} <span class="small text-muted">({{ $blocks->count() }})</span></h2>
                <a href="{{ route('admin.sections.create', ['page' => $page, 'section' => $sectionKey]) }}" class="btn btn-ghost btn-sm">+ Add block</a>
            </div>
            <div class="card">
                @forelse ($blocks as $block)
                    <div class="content-item">
                        <div>
                            @if ($block->image_path)
                                <img src="{{ $block->imageUrl() }}" alt="" class="thumb" style="width: 72px; height: 48px;">
                            @else
                                <span class="badge">{{ $block->badge ?: '—' }}</span>
                            @endif
                        </div>
                        <div class="content-item__body">
                            <h4>{{ $block->title ?: '(no title)' }}
                                @unless ($block->is_active)<span class="badge badge--red">Hidden</span>@endunless
                            </h4>
                            <p>{{ Str::limit($block->body, 140) }}</p>
                        </div>
                        <div class="inline-actions">
                            <a href="{{ route('admin.sections.edit', $block) }}" class="btn btn-ghost btn-sm">Edit</a>
                            <form action="{{ route('admin.sections.destroy', $block) }}" method="POST" data-confirm="Delete this content block?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger-outline btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty"><p>No blocks yet. This part of the page is hidden until you add one.</p></div>
                @endforelse
            </div>
        </div>
    @endforeach
@endsection
