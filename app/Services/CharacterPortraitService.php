<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Http\UploadedFile;

class CharacterPortraitService
{
    public function __construct(private readonly OptimizedImageService $images) {}

    public function replace(Character $character, UploadedFile $portrait): void
    {
        $path = $this->images->storeSquarePortrait($portrait, 'portraits');
        $this->images->deletePublicUrl($character->portrait_path, 'portraits');
        $character->update(['portrait_path' => $path]);
    }

    public function remove(Character $character): void
    {
        $this->images->deletePublicUrl($character->portrait_path, 'portraits');
        $character->update(['portrait_path' => null]);
    }
}
