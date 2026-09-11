<?php

namespace App\Services\Bestiary;

use App\Models\Monster;
use App\Services\OptimizedImageService;
use Illuminate\Http\UploadedFile;

class MonsterPortraitService
{
    public function __construct(private readonly OptimizedImageService $images) {}

    public function replace(Monster $monster, UploadedFile $portrait): void
    {
        $path = $this->images->storeSquarePortrait($portrait, 'portraits/monsters');
        $this->images->deletePublicUrl($monster->portrait_path, 'portraits/monsters');
        $monster->update(['portrait_path' => $path]);
    }

    public function remove(Monster $monster): void
    {
        $this->images->deletePublicUrl($monster->portrait_path, 'portraits/monsters');
        $monster->update(['portrait_path' => null]);
    }
}
