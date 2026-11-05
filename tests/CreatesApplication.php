<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = $app->make('config')->get('database.default');
        $database = (string) $app->make('config')->get("database.connections.{$connection}.database");

        if (! $app->environment('testing') || ! str_ends_with($database, '_test')) {
            throw new \RuntimeException(
                "Refusing to run tests against database [{$database}] in [{$app->environment()}] environment. "
                .'Clear cached config and use a *_test database.'
            );
        }

        return $app;
    }
}
