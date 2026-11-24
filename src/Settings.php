<?php

declare(strict_types=1);

namespace Chrismou\Rollbar;

use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Ui\Controls\BooleanMenu;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use Illuminate\Validation\Rule;
use Override;

/**
 * Rollbar plugin settings.
 *
 * Settings marked as env-parseable hold either a literal or a `$ENV_VAR` reference, so always
 * read them through their getters rather than the raw properties.
 */
class Settings extends PluginSettings
{
    /** @var list<string> The log levels that can be chosen as the reporting threshold. */
    public const array LOG_LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /** @var bool|string Whether anything is sent to Rollbar. Env-parseable. */
    public bool|string $pluginEnabled = true;

    /** @var string|null The project's `post_server_item` access token. Env-parseable. */
    public ?string $accessToken = null;

    /** @var string The minimum log level that is reported to Rollbar. */
    public string $logLevel = 'error';

    /** @var bool Whether the Rollbar browser JS snippet is added to front-end templates. */
    public bool $enableJs = false;

    /** @var string|null The project's `post_client_item` access token. Env-parseable. */
    public ?string $postClientItemAccessToken = null;

    /** @var string|null Comma-separated list of exception classes that are never reported. Env-parseable. */
    public ?string $exceptionIgnoreList = null;

    /** @return array<string, mixed> */
    #[Override]
    public function getRules(): array
    {
        return [
            'pluginEnabled' => new EnvValueRule(['boolean']),
            'accessToken' => ['nullable', 'string'],
            'logLevel' => ['required', Rule::in(self::LOG_LEVELS)],
            'enableJs' => ['boolean'],
            'postClientItemAccessToken' => ['nullable', 'string'],
            'exceptionIgnoreList' => ['nullable', 'string'],
        ];
    }

    /**
     * Normalises `pluginEnabled` to the '1', '0' or `$ENV_VAR` value its settings menu works with.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'pluginEnabled' => BooleanMenu::optionValue($this->pluginEnabled),
        ];
    }

    public function isPluginEnabled(): bool
    {
        return Env::parseBoolean($this->pluginEnabled) ?? false;
    }

    public function getAccessToken(): ?string
    {
        return Env::parse($this->accessToken) ?: null;
    }

    public function getPostClientItemAccessToken(): ?string
    {
        return Env::parse($this->postClientItemAccessToken) ?: null;
    }

    /**
     * Returns the exception classes to ignore, without leading backslashes.
     *
     * @return list<class-string>
     */
    public function getExceptionsToIgnore(): array
    {
        $exceptionIgnoreList = Env::parse($this->exceptionIgnoreList) ?? '';

        return array_values(array_filter(array_map(
            static fn (string $class): string => ltrim(trim($class), '\\'),
            explode(',', $exceptionIgnoreList),
        )));
    }
}
