<?php

declare(strict_types=1);

namespace Chrismou\Rollbar;

use Chrismou\Rollbar\Logging\RollbarChannel;
use Illuminate\Support\ServiceProvider;

/**
 * Starts reporting to Rollbar before Craft boots, when the access token is in the `ROLLBAR_TOKEN` env var.
 *
 * Craft only loads plugins (and their settings) once it can reach the database, so the plugin alone can't
 * report anything that goes wrong before that - a database outage, for example. This provider is discovered
 * by Laravel itself, so it runs regardless of whether the plugin is installed or enabled in Craft.
 */
class EarlyReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RollbarChannel::class);

        $rollbarChannel = $this->app->make(RollbarChannel::class);

        if ($rollbarChannel->hasEnvToken()) {
            // rollbar-laravel reads the token (and ROLLBAR_LEVEL, if set) from the env itself
            $rollbarChannel->register(accessToken: null);
        }
    }
}
