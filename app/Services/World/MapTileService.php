<?php

namespace App\Services\World;

use App\Models\GameMap;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Découpage d'une carte en tuiles, et service de ces tuiles.
 *
 * ── Pourquoi découper côté serveur ────────────────────────────────────────
 * Poser un voile CSS sur les cases non révélées ne cache rien : l'image
 * complète part quand même dans le navigateur et se lit dans l'onglet Réseau.
 * On découpe donc l'image à l'upload, et la tuile d'une case fermée n'est
 * jamais servie — c'est la seule façon d'avoir un brouillard réel.
 *
 * Les tuiles vivent sur le disque privé : aucune n'est joignable par URL
 * directe, tout passe par la route qui vérifie la révélation.
 */
class MapTileService
{
    /** Disque privé : rien ici n'est exposé par le serveur web. */
    private const DISK = 'local';

    /** Une carte trop finement quadrillée produirait des milliers de fichiers. */
    public const MAX_COLUMNS = 40;

    public const MAX_ROWS = 40;

    /**
     * Enregistre l'image et la découpe.
     *
     * @return array{image_path: string, image_width: int, image_height: int}
     */
    public function slice(GameMap $map, UploadedFile $file, int $columns, int $rows): array
    {
        $columns = max(1, min($columns, self::MAX_COLUMNS));
        $rows = max(1, min($rows, self::MAX_ROWS));

        $source = $this->readImage($file);
        $width = imagesx($source);
        $height = imagesy($source);

        $this->forget($map);

        // Les bords reçoivent le reste de la division : aucune bande de pixels
        // n'est perdue entre la dernière tuile et le bord de l'image.
        $tileWidth = (int) ceil($width / $columns);
        $tileHeight = (int) ceil($height / $rows);

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $x = $column * $tileWidth;
                $y = $row * $tileHeight;
                $sliceWidth = min($tileWidth, $width - $x);
                $sliceHeight = min($tileHeight, $height - $y);

                $tile = imagecreatetruecolor($sliceWidth, $sliceHeight);
                imagecopy($tile, $source, 0, 0, $x, $y, $sliceWidth, $sliceHeight);

                ob_start();
                imagewebp($tile, null, 82);
                Storage::disk(self::DISK)->put($this->tilePath($map, $column, $row), ob_get_clean());

                imagedestroy($tile);
            }
        }

        imagedestroy($source);

        // L'image entière reste stockée en privé : elle sert d'aperçu au MJ et
        // permet de re-découper si le quadrillage change.
        $original = $file->storeAs($this->directory($map), 'source.'.$file->getClientOriginalExtension(), self::DISK);

        return ['image_path' => $original, 'image_width' => $width, 'image_height' => $height];
    }

    /** Redécoupe depuis l'image déjà stockée, quand seul le quadrillage change. */
    public function reslice(GameMap $map, int $columns, int $rows): void
    {
        if (! $map->image_path || ! Storage::disk(self::DISK)->exists($map->image_path)) {
            return;
        }

        $source = imagecreatefromstring(Storage::disk(self::DISK)->get($map->image_path));

        if ($source === false) {
            throw new RuntimeException('Image de carte illisible.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $tileWidth = (int) ceil($width / $columns);
        $tileHeight = (int) ceil($height / $rows);

        foreach (Storage::disk(self::DISK)->files($this->directory($map).'/tiles') as $stale) {
            Storage::disk(self::DISK)->delete($stale);
        }

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $x = $column * $tileWidth;
                $y = $row * $tileHeight;
                $sliceWidth = min($tileWidth, $width - $x);
                $sliceHeight = min($tileHeight, $height - $y);

                $tile = imagecreatetruecolor($sliceWidth, $sliceHeight);
                imagecopy($tile, $source, 0, 0, $x, $y, $sliceWidth, $sliceHeight);

                ob_start();
                imagewebp($tile, null, 82);
                Storage::disk(self::DISK)->put($this->tilePath($map, $column, $row), ob_get_clean());

                imagedestroy($tile);
            }
        }

        imagedestroy($source);
    }

    /** Contenu binaire d'une tuile, ou null si elle n'a pas été produite. */
    public function tileContents(GameMap $map, int $column, int $row): ?string
    {
        $path = $this->tilePath($map, $column, $row);

        return Storage::disk(self::DISK)->exists($path)
            ? Storage::disk(self::DISK)->get($path)
            : null;
    }

    /** Supprime toutes les tuiles et l'image source d'une carte. */
    public function forget(GameMap $map): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->directory($map));
    }

    private function directory(GameMap $map): string
    {
        return 'maps/'.$map->getKey();
    }

    private function tilePath(GameMap $map, int $column, int $row): string
    {
        return $this->directory($map)."/tiles/{$row}-{$column}.webp";
    }

    /** Décode l'upload en ressource GD, quel que soit le format accepté. */
    private function readImage(UploadedFile $file)
    {
        $image = imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new RuntimeException('Format d’image non reconnu.');
        }

        return $image;
    }
}
