<?php

declare(strict_types=1);

namespace Chrismou\Rollbar\Logging;

use Chrismou\Rollbar\EarlyReportingServiceProvider;
use Chrismou\Rollbar\Settings;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Env;
use Rollbar\Laravel\MonologHandler;
use Rollbar\Laravel\RollbarServiceProvider;
use Rollbar\RollbarLogger;
use Throwable;

use function CraftCms\Cms\currentUser;

/**
 * Wires rollbar/rollbar-laravel's `rollbar` log channel into the app.
 *
 * Registered as a singleton, because it is used twice per request: once by
 * {@see EarlyReportingServiceProvider} before Craft boots, and again by the plugin once
 * its settings are available.
 */
final class RollbarChannel
{
    public const string CHANNEL = 'rollbar';

    /** The stack channel that wraps the app's default channel together with the Rollbar channel. */
    public const string STACK_CHANNEL = 'chrismou-rollbar';

    /** The env var rollbar-laravel reads the access token from, which makes reporting start before Craft boots. */
    public const string TOKEN_ENV_VAR = 'ROLLBAR_TOKEN';

    /** The env var rollbar-laravel reads the minimum log level from. */
    public const string LEVEL_ENV_VAR = 'ROLLBAR_LEVEL';

    public const string DEFAULT_LEVEL = 'error';

    /** @var array<string, mixed>|null The `logging.channels.rollbar` config the project defined itself. */
    private ?array $projectChannelConfig = null;

    /** The default log channel before the Rollbar stack was put in front of it. */
    private ?string $originalDefaultChannel = null;

    public function __construct(
        private readonly Application $app,
        private readonly Repository $config,
    ) {}

    /**
     * Whether a Rollbar logger is available, either from the plugin's settings or the project's own config.
     */
    public function isActive(): bool
    {
        return $this->app->bound(RollbarLogger::class);
    }

    /**
     * Whether the access token comes from the `ROLLBAR_TOKEN` env var, so reporting starts before Craft boots.
     */
    public function hasEnvToken(): bool
    {
        return ! empty(Env::get(self::TOKEN_ENV_VAR));
    }

    /**
     * Defines the `rollbar` channel and makes sure the app's default log channel reports to it.
     *
     * Values the project already defines under `logging.channels.rollbar` (or `ROLLBAR_*` env vars) win
     * over the ones passed in. Safe to call again with better values once the plugin's settings are known.
     */
    public function register(?string $accessToken, string $level = self::DEFAULT_LEVEL): void
    {
        $this->projectChannelConfig ??= (array) $this->config->get('logging.channels.'.self::CHANNEL, []);

        $this->config->set('logging.channels.'.self::CHANNEL, array_merge(
            [
                'driver' => 'monolog',
                'handler' => MonologHandler::class,
                'access_token' => $accessToken,
                'level' => $level,
            ],
            $this->projectChannelConfig,
        ));

        // rollbar-laravel's provider skips itself when it's discovered before a token is configured
        if (! $this->isActive()) {
            $this->app->register(RollbarServiceProvider::class, force: true);
        }

        if (! $this->isActive()) {
            return;
        }

        $this->addToDefaultLogChannel();
        $this->applyLevel();
    }

    /**
     * Takes Rollbar back out of the default log channel, for when the plugin is switched off in its settings.
     */
    public function deactivate(): void
    {
        if ($this->originalDefaultChannel === null) {
            return;
        }

        $this->config->set('logging.default', $this->originalDefaultChannel);
        $this->app->make(LogManager::class)->forgetChannel(self::STACK_CHANNEL);
        $this->originalDefaultChannel = null;
    }

    /**
     * Adds Craft-specific context to everything reported, without replacing callbacks the project set itself.
     */
    public function applyCraftContext(Settings $settings): void
    {
        if (! $this->isActive()) {
            return;
        }

        $logger = $this->app->make(RollbarLogger::class);
        $existingConfig = $logger->extend([]);
        $exceptionsToIgnore = $settings->getExceptionsToIgnore();

        $logger->configure(array_diff_key(array_filter([
            'person_fn' => $this->currentPerson(...),
            'custom_data_method' => $this->craftContext(...),
            'check_ignore' => $exceptionsToIgnore === []
                ? null
                : static fn (bool $isUncaught, mixed $toLog): bool => self::isIgnoredException($toLog, $exceptionsToIgnore),
        ]), $existingConfig));
    }

    /**
     * Routes the default log channel through a stack that also includes the Rollbar channel.
     */
    private function addToDefaultLogChannel(): void
    {
        $defaultChannel = $this->config->get('logging.default');

        if ($defaultChannel === null || $this->channelReportsToRollbar($defaultChannel)) {
            return;
        }

        $this->originalDefaultChannel = $defaultChannel;

        $this->config->set('logging.channels.'.self::STACK_CHANNEL, [
            'driver' => 'stack',
            'channels' => [$defaultChannel, self::CHANNEL],
            // A Rollbar outage must never take the site down with it
            'ignore_exceptions' => true,
        ]);

        $this->config->set('logging.default', self::STACK_CHANNEL);
    }

    /**
     * Applies the configured level to the handler, which may already exist from before Craft booted.
     */
    private function applyLevel(): void
    {
        $level = Env::get(self::LEVEL_ENV_VAR) ?: $this->config->get('logging.channels.'.self::CHANNEL.'.level');

        $this->app->make(MonologHandler::class)->setLevel($level);
    }

    private function channelReportsToRollbar(string $channel): bool
    {
        if (in_array($channel, [self::CHANNEL, self::STACK_CHANNEL], true)) {
            return true;
        }

        $channelConfig = (array) $this->config->get("logging.channels.$channel", []);

        return ($channelConfig['driver'] ?? null) === 'stack'
            && in_array(self::CHANNEL, (array) ($channelConfig['channels'] ?? []), true);
    }

    /**
     * @param  list<class-string>  $exceptionsToIgnore
     */
    private static function isIgnoredException(mixed $toLog, array $exceptionsToIgnore): bool
    {
        if (! $toLog instanceof Throwable) {
            return false;
        }

        return array_any($exceptionsToIgnore, static fn (string $class): bool => $toLog instanceof $class);
    }

    /**
     * @return array{id: string, username: ?string, email: ?string}|null
     */
    private function currentPerson(): ?array
    {
        $user = currentUser();
        $userId = $user?->getCraftUserId();

        if ($userId === null) {
            return null;
        }

        $userElement = $user->asElement();

        // Rollbar only keeps the username and email when `capture_username` / `capture_email` are enabled
        return [
            'id' => (string) $userId,
            'username' => $userElement->username,
            'email' => $userElement->email,
        ];
    }

    /**
     * @return array{craft: array<string, mixed>}
     */
    private function craftContext(): array
    {
        $isConsole = $this->app->runningInConsole();

        return [
            'craft' => array_filter([
                'version' => Cms::version(),
                'edition' => Edition::get()->name,
                'site' => ! $isConsole && Sites::getHasCurrentSite() ? Sites::getCurrentSite()->handle : null,
                'requestType' => match (true) {
                    $isConsole => 'console',
                    request()->isCpRequest() => 'cp',
                    default => 'site',
                },
            ]),
        ];
    }
}
