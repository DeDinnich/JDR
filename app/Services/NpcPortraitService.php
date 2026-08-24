<?php

namespace App\Services;

use App\Models\Npc;
use Illuminate\Http\UploadedFile;

class NpcPortraitService
{
    public function __construct(private readonly OptimizedImageService $images) {}

    public function replace(Npc $npc, UploadedFile $portrait): void
    {
        $path = $this->images->storeSquarePortrait($portrait, 'portraits/npcs');
        $this->images->deletePublicUrl($npc->portrait_path, 'portraits/npcs');
        $npc->update(['portrait_path' => $path]);
    }

    public function remove(Npc $npc): void
    {
        $this->images->deletePublicUrl($npc->portrait_path, 'portraits/npcs');
        $npc->update(['portrait_path' => null]);
    }
}
