<?php

namespace SlothDevGuy\Searches;

use Illuminate\Support\ServiceProvider;
use SlothDevGuy\Searches\Support\SearchGuard;

class EloquentSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/searches.php', 'searches');

        $this->app->singleton(SearchGuard::class, fn ($app) => new SearchGuard(
            strict: (bool) $app['config']->get('searches.strict', false),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/searches.php' => config_path('searches.php'),
            ], 'searches-config');
        }
    }
}
