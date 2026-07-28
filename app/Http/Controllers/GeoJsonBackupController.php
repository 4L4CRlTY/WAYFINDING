<?php

namespace App\Http\Controllers;

use App\Services\GeoJsonBackupService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeoJsonBackupController extends Controller
{
    public function __construct(
        private readonly GeoJsonBackupService $backups,
    ) {}

    public function index(): View
    {
        $files = $this->backups->files();
        $outdoorFiles = $files->where('group', 'Outdoor Source Files')->values();
        $indoorFiles = $files->where('group', 'Indoor Building Files')->values();

        return view('admin.geojson_backups.index', [
            'outdoorFiles' => $outdoorFiles,
            'indoorBuildings' => $indoorFiles
                ->groupBy('building_label')
                ->sortKeys(),
            'fileCount' => $files->count(),
            'imageCount' => $files->where('file_kind', 'image')->count(),
            'totalSizeLabel' => $this->formatBytes((int) $files->sum('size')),
        ]);
    }

    public function download(string $dataset): BinaryFileResponse
    {
        $file = $this->backups->find($dataset);

        abort_unless($file && is_file($file['absolute_path']), 404);

        return response()->download(
            $file['absolute_path'],
            $file['filename'],
            [
                'Content-Type' => $file['mime_type'],
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }

    public function downloadAll(): BinaryFileResponse
    {
        abort_unless(class_exists(\PharData::class), 503, 'Complete backup packaging is not available on this server.');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $temporaryBase = tempnam(sys_get_temp_dir(), 'wayfinding_geojson_');

        abort_if($temporaryBase === false, 500, 'Unable to prepare the backup package.');

        @unlink($temporaryBase);
        $archivePath = $temporaryBase.'.tar';
        $archive = new \PharData($archivePath);

        foreach ($this->backups->files() as $file) {
            $archive->addFile(
                $file['absolute_path'],
                'original_files/'.$file['relative_path'],
            );
        }

        $archive->addFromString('README.txt', $this->readme($timestamp));
        unset($archive);

        return response()
            ->download(
                $archivePath,
                "wayfinding_geojson_backup_{$timestamp}.tar",
                [
                    'Content-Type' => 'application/x-tar',
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'private, no-store, max-age=0',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    private function readme(string $timestamp): string
    {
        return <<<TEXT
Smart Campus Wayfinding Map & Floorplan Backup
Generated: {$timestamp}

This package contains byte-for-byte copies of current GeoJSON source files and
indoor floorplan images. Files are not reconstructed, resized, or recompressed.

Contents:
- Original outdoor GeoJSON uploads
- Original per-building and per-floor indoor GeoJSON uploads
- Exact current indoor floorplan images
- Exact previous floorplan images when available

Important:
- Files ending in _backup.geojson are automatic previous versions and are not
  included in this current-source package.
- Indoor floor-extent source files uploaded before exact-file retention was
  enabled may be absent until they are uploaded again.
- Keep this archive private because it contains internal campus routing data.
- The original directory layout is preserved inside original_files/.
TEXT;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }
}
