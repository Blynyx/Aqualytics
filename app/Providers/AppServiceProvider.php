<?php

namespace App\Providers;

use App\Models\InternalNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
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
        $this->composeUnreadNotificationsCount();

        Route::bind('notification', function (string $value): InternalNotification {
            return InternalNotification::query()->findOrFail($value);
        });
    }

    private function composeUnreadNotificationsCount(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            if ($user === null) {
                $view->with('unreadNotificationsCount', 0);

                return;
            }

            $view->with(
                'unreadNotificationsCount',
                $user->internalNotifications()->unread()->count(),
            );
        });
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
