<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class FloorplanImageOptimizer
{
    private const MAX_MOBILE_EDGE = 1400;

    private const WEBP_QUALITY = 78;

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

        $targetDirectory = public_path('floorplan_image/mobile');
        File::ensureDirectoryExists($targetDirectory, 0755, true);

        $targetName = pathinfo($safeName, PATHINFO_FILENAME).'.webp';
        $targetPath = $targetDirectory.DIRECTORY_SEPARATOR.$targetName;
        if (is_file($targetPath) && filemtime($targetPath) >= filemtime($sourcePath)) {
            return $targetName;
        }

        $info = @getimagesize($sourcePath);
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        $mime = (string) ($info['mime'] ?? '');
        if ($width < 1 || $height < 1) {
            return null;
        }

        $source = match ($mime) {
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => false,
        };
        if (! $source) {
            return null;
        }

        $scale = min(1, self::MAX_MOBILE_EDGE / max($width, $height));
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

        $saved = imagewebp($canvas, $targetPath, self::WEBP_QUALITY);
        imagedestroy($canvas);
        imagedestroy($source);

        return $saved ? $targetName : null;
    }
}
