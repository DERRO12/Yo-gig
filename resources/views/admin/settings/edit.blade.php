@extends('layouts.admin')

@section('title', 'Site settings')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Site settings</h1>
            <p>Branding, contact details, payment accounts and platform rules. Changes go live immediately.</p>
        </div>
    </div>

    <nav class="tabs">
        @foreach ($groups as $key => $groupConfig)
            <a href="{{ route('admin.settings.edit', $key) }}" class="{{ $group === $key ? 'is-active' : '' }}">{{ $groupConfig['label'] }}</a>
        @endforeach
    </nav>

    <form action="{{ route('admin.settings.update', $group) }}" method="POST" enctype="multipart/form-data" class="card card-body" style="max-width: 820px;">
        @csrf
        @method('PUT')

        @foreach ($fields as $key => $field)
            @php($value = old($key, $site->all()[$key] ?? $field['default']))

            @switch($field['type'])
                @case('textarea')
                    <div class="form-group">
                        <label for="{{ $key }}">{{ $field['label'] }}</label>
                        <textarea id="{{ $key }}" name="{{ $key }}" class="input" rows="3">{{ $value }}</textarea>
                    </div>
                    @break

                @case('boolean')
                    <div class="form-group">
                        <label class="checkbox"><input type="checkbox" name="{{ $key }}" value="1" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))> {{ $field['label'] }}</label>
                    </div>
                    @break

                @case('image')
                    @include('admin.partials.image-field', ['name' => $key, 'label' => $field['label'], 'currentUrl' => $site->image($key), 'hint' => 'JPG, PNG or WebP, up to 2 MB.'])
                    @break

                @default
                    <div class="form-group">
                        <label for="{{ $key }}">{{ $field['label'] }}</label>
                        <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text')) }}"
                               id="{{ $key }}" name="{{ $key }}" class="input" value="{{ $value }}" @if ($field['type'] === 'number') step="any" min="0" @endif>
                    </div>
            @endswitch
        @endforeach

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save {{ strtolower($groups[$group]['label']) }}</button>
        </div>
    </form>
@endsection
