<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class FloorplanImageOptimizer
{
    private const MAX_MOBILE_EDGE = 1400;

    private const WEBP_QUALITY = 78;

    private const MAX_LOW_END_EDGE = 960;

    private const LOW_END_WEBP_QUALITY = 72;

    public function optimize(string $fileName): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $safeName = basename($fileName);
        $sourcePath = public_path('floorplan_image/'.$safeName);
        if (! is_file($sourcePath)) {
            return null;
        }

        $targetName = pathinfo($safeName, PATHINFO_FILENAME).'.webp';
        $mobileSaved = $this->createVariant(
            $sourcePath,
            public_path('floorplan_image/mobile'),
            $targetName,
            self::MAX_MOBILE_EDGE,
            self::WEBP_QUALITY,
        );

        /* A smaller decoded bitmap reduces pinch/drag work on low-memory
           phones. The original and the normal mobile image remain intact. */
        $this->createVariant(
            $sourcePath,
            public_path('floorplan_image/mobile-low'),
            $targetName,
            self::MAX_LOW_END_EDGE,
            self::LOW_END_WEBP_QUALITY,
        );

        return $mobileSaved ? $targetName : null;
    }

    private function createVariant(
        string $sourcePath,
        string $targetDirectory,
        string $targetName,
        int $maxEdge,
        int $quality,
    ): bool {
        File::ensureDirectoryExists($targetDirectory, 0755, true);
        $targetPath = $targetDirectory.DIRECTORY_SEPARATOR.$targetName;
        if (is_file($targetPath) && filemtime($targetPath) >= filemtime($sourcePath)) {
            return true;
        }

        $info = @getimagesize($sourcePath);
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        $mime = (string) ($info['mime'] ?? '');
        if ($width < 1 || $height < 1) {
            return false;
        }

        $source = match ($mime) {
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => false,
        };
        if (! $source) {
            return false;
        }

        $scale = min(1, $maxEdge / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height,
        );

        $saved = imagewebp($canvas, $targetPath, $quality);
        imagedestroy($canvas);
        imagedestroy($source);

        return $saved;
    }
}
