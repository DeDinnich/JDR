<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OptimizedImageService
{
    public function storeSquarePortrait(UploadedFile $file, string $directory): string
    {
        $path = trim($directory, '/').'/'.Str::ulid().'.webp';
        $stored = Image::fromUpload($file)
            ->orient()
            ->cover(768, 768)
            ->optimize('webp', 82)
            ->storeAs(trim($directory, '/'), basename($path), 'public');

        if ($stored === false) {
            throw new \RuntimeException('Impossible d’enregistrer le portrait optimisé.');
        }

        return Storage::disk('public')->url($path);
    }

    public function deletePublicUrl(?string $url, string $directory): void
    {
        $prefix = '/storage/'.trim($directory, '/').'/';

        if ($url === null || ! str_contains($url, $prefix)) {
            return;
        }

        Storage::disk('public')->delete(trim($directory, '/').'/'.basename($url));
    }
}
