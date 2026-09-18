<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->resolveRelativeSqlitePath();
    }

    /**
     * Resolve relative SQLite paths against the project root so Dusk and
     * other sqlite file connections work the same on Windows, Linux, and Docker.
     */
    private function resolveRelativeSqlitePath(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === ':memory:' || $this->isAbsolutePath($database)) {
            return;
        }

        config([
            'database.connections.sqlite.database' => base_path($database),
        ]);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
