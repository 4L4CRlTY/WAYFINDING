<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to boot any feature test outside the dedicated MySQL schema.
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
        $username = (string) $app['config']->get(
            "database.connections.{$connection}.username"
        );

        if (
            ! $app->environment('testing')
            || $connection !== 'mysql'
            || $database !== 'wayfinding_testing'
            || $username !== 'wayfinding_test'
        ) {
            throw new RuntimeException(
                'Test safety stop: PHPUnit must use the restricted '
                .'wayfinding_test account and wayfinding_testing database. '
                .'Clear cached configuration and check .env.testing.'
            );
        }

        return $app;
    }
}
