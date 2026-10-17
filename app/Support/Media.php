<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Media
{
    /**
     * Resolve a stored image path to a public URL.
     *
     * Paths under "images/" are bundled assets in /public; anything else
     * was uploaded through the admin panel to the public disk.
     */
    public static function url(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback ? asset($fallback) : null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Replace an uploaded image, deleting the old upload when it was not a bundled asset.
     */
    public static function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        static::delete($oldPath);

        return static::store($file, $directory);
    }

    public static function delete(?string $path): void
    {
        if (blank($path) || str_starts_with($path, 'images/') || str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
