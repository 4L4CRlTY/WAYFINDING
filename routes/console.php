<?php

use App\Services\CampusSnapshotPublisher;
use App\Services\DestinationKeywordSynchronizer;
use App\Support\WayfindingCache;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('destination-keywords:sync', function () {
    $result = app(DestinationKeywordSynchronizer::class)->sync();

    $this->info(
        "{$result['created']} missing keyword(s) generated for "
        ."{$result['buildings']} buildings and {$result['rooms']} rooms. "
        ."{$result['existing']} existing keyword(s) preserved."
    );
})->purpose('Generate missing search aliases for all buildings and indoor rooms');

Artisan::command('wayfinding:snapshot', function () {
    app(WayfindingCache::class)->invalidate();
    $result = app(CampusSnapshotPublisher::class)->publish();

    $kilobytes = number_format($result['bytes'] / 1024, 1);
    $this->info(
        "Campus snapshot v{$result['cache_version']} published: "
        ."{$result['datasets']} datasets, {$result['keywords']} keywords, {$kilobytes} KiB."
    );
})->purpose('Publish the public campus navigation snapshot');
