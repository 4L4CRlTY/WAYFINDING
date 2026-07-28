<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to boot any feature test against a persistent database.
     *
     * Laravel's cached configuration can otherwise override phpunit.xml and
     * make RefreshDatabase target the developer's real MySQL schema.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get(
            "database.connections.{$connection}.database"
        );

        if (
            ! $app->environment('testing')
            || $connection !== 'sqlite'
            || $database !== ':memory:'
        ) {
            throw new RuntimeException(
                'Test safety stop: PHPUnit must use the in-memory SQLite database. '
                .'Clear cached configuration before running the test suite.'
            );
        }

        return $app;
    }
}
