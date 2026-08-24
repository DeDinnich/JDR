<?php

namespace App\Services\World;

use App\Models\GameMap;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use RuntimeException;

class MapPreviewService
{
    public function render(GameMap $map, bool $withFog): string
    {
        if (! $map->image_path || ! Storage::disk('local')->exists($map->image_path)) {
            throw new RuntimeException('Image de carte introuvable.');
        }

        $preview = ImageManager::usingDriver(Driver::class)
            ->decodeBinary(Storage::disk('local')->get($map->image_path))
            ->orient()
            ->scaleDown(width: 960);

        if ($withFog) {
            $revealed = $map->revealedCellKeys();
            $cellWidth = $preview->width() / max(1, $map->grid_columns);
            $cellHeight = $preview->height() / max(1, $map->grid_rows);

            for ($row = 0; $row < $map->grid_rows; $row++) {
                for ($column = 0; $column < $map->grid_columns; $column++) {
                    if (isset($revealed[$column.':'.$row])) {
                        continue;
                    }

                    $x = (int) floor($column * $cellWidth);
                    $y = (int) floor($row * $cellHeight);
                    $width = (int) ceil(($column + 1) * $cellWidth) - $x;
                    $height = (int) ceil(($row + 1) * $cellHeight) - $y;

                    $preview->drawRectangle(function (RectangleFactory $rectangle) use ($x, $y, $width, $height): void {
                        $rectangle->size($width, $height)
                            ->at($x, $y)
                            ->background('rgba(7, 10, 8, 0.96)')
                            ->border('rgba(202, 165, 91, 0.10)');
                    });
                }
            }
        }

        return $preview->encode(new WebpEncoder(quality: 78))->toString();
    }
}
