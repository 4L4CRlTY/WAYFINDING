@extends('admin.dashboard')

@section('admin')
<style>
    .backup-center {
        padding: 24px 4px 38px;
        color: #18375d;
    }

    .backup-hero {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 24px;
        overflow: hidden;
        padding: 30px;
        border: 1px solid rgba(104, 167, 238, .45);
        border-radius: 24px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 20%, rgba(104, 167, 238, .48), transparent 32%),
            linear-gradient(135deg, #18375d, #28578d);
        box-shadow: 0 20px 48px rgba(24, 55, 93, .16);
    }

    .backup-hero::after {
        position: absolute;
        right: -70px;
        bottom: -105px;
        width: 270px;
        height: 270px;
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 50%;
        content: "";
    }

    .backup-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 11px;
        color: #cfe7ff;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .backup-kicker::before {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #68a7ee;
        box-shadow: 0 0 15px #9dcaff;
        content: "";
    }

    .admin-future .content-page .backup-hero h1 {
        margin: 0;
        color: #fff !important;
        font-size: clamp(26px, 3vw, 42px);
        font-weight: 800;
        letter-spacing: -.04em;
    }

    .backup-hero p {
        max-width: 760px;
        margin: 10px 0 0;
        color: #e9f4ff;
        font-size: 14px;
        line-height: 1.65;
    }

    .backup-all-button {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 48px;
        padding: 12px 18px;
        border: 1px solid rgba(255, 255, 255, .65);
        border-radius: 13px;
        color: #18375d;
        background: #fff;
        box-shadow: 0 12px 28px rgba(5, 27, 53, .22);
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .backup-all-button:hover {
        color: #18375d;
        transform: translateY(-1px);
    }

    .backup-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin: 18px 0 24px;
    }

    .backup-stat {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid #cfe1f4;
        border-radius: 16px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 10px 26px rgba(24, 55, 93, .08);
    }

    .backup-stat i {
        display: grid;
        flex: 0 0 42px;
        width: 42px;
        height: 42px;
        place-items: center;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #18375d, #68a7ee);
        font-size: 20px;
    }

    .backup-stat strong {
        display: block;
        color: #18375d;
        font-size: 20px;
        font-weight: 800;
    }

    .backup-stat span {
        display: block;
        margin-top: 2px;
        color: #68819c;
        font-size: 11px;
    }

    .backup-notice {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 24px;
        padding: 15px 17px;
        border: 1px solid #bcd8f3;
        border-radius: 15px;
        color: #385d80;
        background: #eef7ff;
        font-size: 12px;
        line-height: 1.55;
    }

    .backup-search {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        padding: 12px 15px;
        border: 1px solid #c9def3;
        border-radius: 14px;
        background: #fff;
    }

    .backup-search i {
        color: #3978ba;
        font-size: 18px;
    }

    .backup-search input {
        width: 100%;
        border: 0;
        outline: 0;
        color: #18375d;
        background: transparent;
        font-size: 13px;
    }

    .backup-notice i {
        margin-top: 1px;
        color: #2b75bb;
        font-size: 19px;
    }

    .backup-group {
        margin-top: 24px;
        padding: 22px;
        border: 1px solid #cfe1f4;
        border-radius: 21px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 15px 38px rgba(24, 55, 93, .08);
    }

    .backup-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 17px;
    }

    .admin-future .content-page .backup-group-header h2 {
        margin: 0;
        color: #18375d !important;
        font-size: 18px;
        font-weight: 800;
    }

    .backup-group-header span {
        padding: 5px 9px;
        border-radius: 999px;
        color: #285f95;
        background: #e7f2ff;
        font-size: 10px;
        font-weight: 800;
    }

    .backup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 13px;
    }

    .backup-dataset {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 13px;
        min-width: 0;
        padding: 15px;
        border: 1px solid #d5e5f5;
        border-radius: 15px;
        background: linear-gradient(145deg, #fff, #f7fbff);
        transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .backup-dataset:hover {
        border-color: #68a7ee;
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(24, 55, 93, .09);
    }

    .backup-dataset-icon {
        display: grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border: 1px solid #c6def5;
        border-radius: 12px;
        color: #285f95;
        background: #eaf4ff;
        font-size: 20px;
    }

    .backup-dataset-copy {
        min-width: 0;
    }

    .backup-dataset-copy strong {
        display: block;
        color: #18375d;
        font-size: 13px;
        font-weight: 800;
    }

    .backup-dataset-copy p {
        margin: 3px 0 0;
        color: #68819c;
        font-size: 10px;
        line-height: 1.45;
    }

    .backup-dataset-count {
        display: inline-block;
        margin-top: 6px;
        color: #3474b2;
        font-size: 10px;
        font-weight: 800;
    }

    .backup-file-path {
        display: block;
        overflow: hidden;
        margin-top: 5px;
        color: #5a7490;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 9px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .backup-empty {
        padding: 28px;
        border: 1px dashed #bcd7f0;
        border-radius: 16px;
        color: #5c7692;
        background: #f8fbff;
        text-align: center;
    }

    .backup-download {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 37px;
        padding: 8px 11px;
        border: 1px solid #bcd7f0;
        border-radius: 10px;
        color: #18375d;
        background: #fff;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .backup-download:hover {
        color: #fff;
        border-color: #18375d;
        background: #18375d;
    }

    @media (max-width: 900px) {
        .backup-hero {
            grid-template-columns: 1fr;
        }

        .backup-all-button {
            width: fit-content;
        }

        .backup-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        .backup-center {
            padding-top: 14px;
        }

        .backup-hero,
        .backup-group {
            padding: 18px;
            border-radius: 17px;
        }

        .backup-stats {
            grid-template-columns: 1fr;
        }

        .backup-dataset {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .backup-download {
            grid-column: 1 / -1;
            width: 100%;
        }
    }
</style>

<main class="backup-center">
    <section class="backup-hero">
        <div>
            <span class="backup-kicker">Administrator Data Safety</span>
            <h1>Map &amp; Floorplan Backup Center</h1>
            <p>
                Download byte-for-byte copies of the current GeoJSON source files and indoor floorplan images.
                Files are grouped per building and are never rebuilt, resized, or recompressed.
            </p>
        </div>

        <a href="{{ route('admin.geojson-backups.download-all') }}" class="backup-all-button">
            <i class="ri-folder-download-line"></i>
            Download Complete Backup
        </a>
    </section>

    <section class="backup-stats" aria-label="Backup summary">
        <div class="backup-stat">
            <i class="ri-file-code-line"></i>
            <div>
                <strong>{{ $fileCount }}</strong>
                <span>Original map files</span>
            </div>
        </div>
        <div class="backup-stat">
            <i class="ri-database-2-line"></i>
            <div>
                <strong>{{ $totalSizeLabel }}</strong>
                <span>Total source-file size</span>
            </div>
        </div>
        <div class="backup-stat">
            <i class="ri-shield-check-line"></i>
            <div>
                <strong>{{ $imageCount }}</strong>
                <span>Exact floorplan images</span>
            </div>
        </div>
    </section>

    <div class="backup-notice">
        <i class="ri-information-line"></i>
        <div>
            <strong>Keep backup files private.</strong>
            The complete package preserves the original directory structure and exact image bytes. Automatic
            <code>_backup.geojson</code> previous GeoJSON versions are not included; current floorplans and
            referenced previous floorplan images are included.
            Old indoor floor extents will appear after their source GeoJSON is uploaded again.
        </div>
    </div>

    <label class="backup-search">
        <i class="ri-search-line"></i>
        <input type="search" id="backupFileSearch" placeholder="Search building, floor, category, or filename">
    </label>

    @if($outdoorFiles->isNotEmpty())
        <section class="backup-group" data-backup-group>
            <div class="backup-group-header">
                <h2>Outdoor Source Files</h2>
                <span>{{ $outdoorFiles->count() }} FILES</span>
            </div>

            <div class="backup-grid">
                @foreach($outdoorFiles as $file)
                    <article
                        class="backup-dataset"
                        data-backup-file
                        data-backup-search="{{ Str::lower($file['category'].' '.$file['location'].' '.$file['relative_path']) }}"
                    >
                        <span class="backup-dataset-icon">
                            <i class="{{ $file['icon'] }}"></i>
                        </span>

                        <div class="backup-dataset-copy">
                            <strong>{{ $file['category'] }}</strong>
                            <p>
                                {{ $file['location'] ?: $file['description'] }}
                            </p>
                            <span class="backup-dataset-count">
                                {{ $file['size_label'] }} · Saved {{ $file['modified_at'] }}
                            </span>
                            <span class="backup-file-path" title="{{ $file['relative_path'] }}">
                                {{ $file['relative_path'] }}
                            </span>
                        </div>

                        <a
                            href="{{ route('admin.geojson-backups.download', ['dataset' => $file['id']]) }}"
                            class="backup-download"
                        >
                            <i class="ri-download-2-line"></i>
                            Download Exact File
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @foreach($indoorBuildings as $buildingName => $buildingFiles)
        <section class="backup-group" data-backup-group>
            <div class="backup-group-header">
                <h2>
                    <i class="ri-building-2-line"></i>
                    {{ $buildingName }}
                </h2>
                <span>{{ $buildingFiles->count() }} FILES</span>
            </div>

            <div class="backup-grid">
                @foreach($buildingFiles->sortBy(fn ($file) => ($file['floor_label'] ?? '').'|'.$file['category']) as $file)
                    <article
                        class="backup-dataset"
                        data-backup-file
                        data-backup-search="{{ Str::lower($buildingName.' '.$file['category'].' '.$file['location'].' '.$file['relative_path']) }}"
                    >
                        <span class="backup-dataset-icon">
                            <i class="{{ $file['icon'] }}"></i>
                        </span>

                        <div class="backup-dataset-copy">
                            <strong>{{ $file['category'] }}</strong>
                            <p>{{ $file['floor_label'] ?: $file['description'] }}</p>
                            <span class="backup-dataset-count">
                                {{ $file['size_label'] }} · Saved {{ $file['modified_at'] }}
                            </span>
                            <span class="backup-file-path" title="{{ $file['relative_path'] }}">
                                {{ $file['relative_path'] }}
                            </span>
                        </div>

                        <a
                            href="{{ route('admin.geojson-backups.download', ['dataset' => $file['id']]) }}"
                            class="backup-download"
                        >
                            <i class="{{ $file['file_kind'] === 'image' ? 'ri-image-download-line' : 'ri-download-2-line' }}"></i>
                            {{ $file['file_kind'] === 'image' ? 'Download Exact Image' : 'Download Exact File' }}
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    @if($outdoorFiles->isEmpty() && $indoorBuildings->isEmpty())
        <div class="backup-empty">
            No original map source files were found on this installation.
        </div>
    @endif
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('backupFileSearch');

    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();

        document.querySelectorAll('[data-backup-group]').forEach(group => {
            let visibleFiles = 0;

            group.querySelectorAll('[data-backup-file]').forEach(file => {
                const matches = !query || file.dataset.backupSearch.includes(query);
                file.hidden = !matches;
                if (matches) visibleFiles += 1;
            });

            group.hidden = visibleFiles === 0;
        });
    });
});
</script>
@endsection
