@extends('layouts.admin')

@section('title', $section->exists ? 'Edit content block' : 'New content block')

@section('content')
    <div class="dash-header">
        <div>
            <a href="{{ route('admin.sections.index', ['page' => $section->page]) }}" class="small">← Page content</a>
            <h1>{{ $section->exists ? 'Edit content block' : 'New content block' }}</h1>
        </div>
    </div>

    <form action="{{ $section->exists ? route('admin.sections.update', $section) : route('admin.sections.store') }}" method="POST" enctype="multipart/form-data" class="card card-body" style="max-width: 820px;">
        @csrf
        @if ($section->exists) @method('PUT') @endif

        <div class="form-row">
            <div class="form-group">
                <label for="page">Page</label>
                <select id="page" name="page" class="input" required>
                    @foreach ($pages as $key => $sectionList)
                        <option value="{{ $key }}" @selected(old('page', $section->page) === $key)>{{ ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="section">Section</label>
                <select id="section" name="section" class="input" required>
                    @foreach ($pages as $key => $sectionList)
                        <optgroup label="{{ ucfirst($key) }}">
                            @foreach ($sectionList as $sectionKey => $sectionLabel)
                                <option value="{{ $sectionKey }}" @selected(old('section', $section->section) === $sectionKey && old('page', $section->page) === $key)>{{ $sectionLabel }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <span class="form-hint">Pick a section from the same page.</span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="badge">Small label / badge</label>
                <input type="text" id="badge" name="badge" class="input" value="{{ old('badge', $section->badge) }}" placeholder="e.g. Step 01, Mission">
            </div>
            <div class="form-group">
                <label for="sort_order">Display order</label>
                <input type="number" id="sort_order" name="sort_order" class="input" min="0" value="{{ old('sort_order', $section->sort_order ?? 0) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="title">Heading</label>
            <input type="text" id="title" name="title" class="input" value="{{ old('title', $section->title) }}">
        </div>

        <div class="form-group">
            <label for="body">Text</label>
            <textarea id="body" name="body" class="input" rows="4">{{ old('body', $section->body) }}</textarea>
        </div>

        @include('admin.partials.image-field', ['name' => 'image', 'label' => 'Background / image', 'currentUrl' => $section->image_path ? $section->imageUrl() : null, 'removable' => true])

        <div class="form-row">
            <div class="form-group">
                <label for="button_text">Main button text</label>
                <input type="text" id="button_text" name="button_text" class="input" value="{{ old('button_text', $section->button_text) }}">
            </div>
            <div class="form-group">
                <label for="button_url">Main button link</label>
                <input type="text" id="button_url" name="button_url" class="input" value="{{ old('button_url', $section->button_url) }}" placeholder="/services or https://...">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="secondary_button_text">Second button text</label>
                <input type="text" id="secondary_button_text" name="secondary_button_text" class="input" value="{{ old('secondary_button_text', $section->secondary_button_text) }}">
            </div>
            <div class="form-group">
                <label for="secondary_button_url">Second button link</label>
                <input type="text" id="secondary_button_url" name="secondary_button_url" class="input" value="{{ old('secondary_button_url', $section->secondary_button_url) }}">
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $section->is_active))> Show on the website</label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $section->exists ? 'Save changes' : 'Create block' }}</button>
            <a href="{{ route('admin.sections.index', ['page' => $section->page]) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
@endsection
