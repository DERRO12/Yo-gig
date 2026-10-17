{{-- Expects: $name, $label, $currentUrl (nullable), optional $hint, optional $removable --}}
<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    @if ($currentUrl)
        <div class="current-image">
            <img src="{{ $currentUrl }}" alt="Current image" class="thumb" style="width: 96px; height: 64px;">
            @if (! empty($removable))
                <label class="checkbox small"><input type="checkbox" name="remove_image" value="1"> Remove image</label>
            @else
                <span class="small text-muted">Upload a new file to replace it.</span>
            @endif
        </div>
    @endif
    <input type="file" id="{{ $name }}" name="{{ $name }}" class="input" accept="image/jpeg,image/png,image/webp" data-preview="{{ $name }}-preview">
    <span class="form-hint">{{ $hint ?? 'JPG, PNG or WebP, up to 4 MB.' }}</span>
    <div class="image-preview" id="{{ $name }}-preview"></div>
</div>
