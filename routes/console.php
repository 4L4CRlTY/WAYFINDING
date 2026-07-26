<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\DestinationKeywordSynchronizer;

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
