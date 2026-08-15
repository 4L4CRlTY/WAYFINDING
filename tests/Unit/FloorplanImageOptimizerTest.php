<?php

namespace Tests\Unit;

use App\Services\FloorplanImageOptimizer;
use Tests\TestCase;

class FloorplanImageOptimizerTest extends TestCase
{
    public function test_it_creates_a_smaller_mobile_floorplan_without_changing_the_original(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('The GD WebP extension is not available.');
        }

        $sourceName = 'optimizer_test_floorplan.png';
        $sourcePath = public_path('floorplan_image/'.$sourceName);
        $mobilePath = public_path('floorplan_image/mobile/optimizer_test_floorplan.webp');
        $lowEndPath = public_path('floorplan_image/mobile-low/optimizer_test_floorplan.webp');
        $image = imagecreatetruecolor(1800, 900);
        $background = imagecolorallocate($image, 238, 246, 255);
        imagefilledrectangle($image, 0, 0, 1799, 899, $background);
        imagepng($image, $sourcePath);
        imagedestroy($image);

        $originalHash = hash_file('sha256', $sourcePath);

        try {
            $result = app(FloorplanImageOptimizer::class)->optimize($sourceName);

            $this->assertSame('optimizer_test_floorplan.webp', $result);
            $this->assertFileExists($mobilePath);
            $this->assertFileExists($lowEndPath);
            $this->assertSame($originalHash, hash_file('sha256', $sourcePath));

            [$width, $height] = getimagesize($mobilePath);
            $this->assertLessThanOrEqual(1400, max($width, $height));

            [$lowWidth, $lowHeight] = getimagesize($lowEndPath);
            $this->assertLessThanOrEqual(960, max($lowWidth, $lowHeight));
        } finally {
            @unlink($sourcePath);
            @unlink($mobilePath);
            @unlink($lowEndPath);
        }
    }
}
