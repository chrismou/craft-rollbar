<?php

declare(strict_types=1);

namespace Chrismou\Rollbar;

use Chrismou\Rollbar\Listeners\InjectBrowserJs;
use Chrismou\Rollbar\Listeners\RecommendEnvToken;
use Chrismou\Rollbar\Logging\RollbarChannel;
use CraftCms\Cms\Cp\Events\CpAlertsResolving;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Ui\Controls\BooleanMenu;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Callout;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\MarkdownContent;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use Override;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

/**
 * Rollbar for Craft CMS.
 *
 * Reports to Rollbar through rollbar/rollbar-laravel's log channel, configured from the plugin's settings.
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;

    protected array $events = [
        PageTemplateRendering::class => InjectBrowserJs::class,
        CpAlertsResolving::class => RecommendEnvToken::class,
    ];

    /**
     * Registers the Rollbar log channel as early as the plugin's settings are available.
     */
    #[Override]
    public function register(): void
    {
        $settings = $this->getSettings();
        $rollbarChannel = $this->app->make(RollbarChannel::class);

        $this->app->bind(Settings::class, fn (): Settings => $this->getSettings());

        if ($settings->isPluginEnabled()) {
            $rollbarChannel->register($settings->getAccessToken(), $settings->logLevel);
        } else {
            $rollbarChannel->deactivate();
        }
    }

    public function boot(RollbarChannel $rollbarChannel): void
    {
        if ($this->getSettings()->isPluginEnabled()) {
            $rollbarChannel->applyCraftContext($this->getSettings());
        }
    }

    #[Override]
    protected static function createSettings(): ?PluginSettings
    {
        return new Settings;
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): ?Ui
    {
        $envTriggers = SelectOptions::getEnvTextExpanderTriggers();
        $envTip = t('This can be set to an environment variable.', category: 'chrismou-rollbar');

        return Ui::make([
            $this->tokenSummaryCallout(),

            Field::make(t('Enable the Rollbar plugin', category: 'chrismou-rollbar'), BooleanMenu::make('pluginEnabled')
                ->yesLabel(t('Enabled'))
                ->noLabel(t('Disabled'))
                ->includeEnvVars())
                ->instructions(t('If disabled, nothing is sent to Rollbar once Craft has loaded.', category: 'chrismou-rollbar')),

            Field::make(t('Access Token', category: 'chrismou-rollbar'), Text::make('accessToken')
                ->monospace()
                ->textExpanderTriggers($envTriggers))
                ->instructions(t('Your project\'s `post_server_item` access token, which you can find in the Rollbar.com interface.', category: 'chrismou-rollbar'))
                ->tip($envTip),

            Field::make(t('Minimum Log Level', category: 'chrismou-rollbar'), Choice::make('logLevel')
                ->options(array_combine(Settings::LOG_LEVELS, array_map(ucfirst(...), Settings::LOG_LEVELS)))
                ->withoutPlaceholder())
                ->instructions(t('Exceptions and log messages at this level or above are reported to Rollbar.', category: 'chrismou-rollbar')),

            MarkdownContent::make('chrismou-rollbar-test-message', sprintf(
                '[%s](%s)',
                t('Send test message to Rollbar', category: 'chrismou-rollbar'),
                cp_url('settings/plugins/chrismou-rollbar/test'),
            )),

            Field::make(t('Enable Rollbar JS integration', category: 'chrismou-rollbar'), Lightswitch::make('enableJs'))
                ->instructions(t('Adds the [Rollbar browser JS](https://docs.rollbar.com/docs/browser-js) snippet to front-end templates.', category: 'chrismou-rollbar')),

            Field::make(t('Post Client Item Access Token', category: 'chrismou-rollbar'), Text::make('postClientItemAccessToken')
                ->monospace()
                ->textExpanderTriggers($envTriggers))
                ->instructions(t('Your project\'s `post_client_item` access token, which you can find in the Rollbar.com interface.', category: 'chrismou-rollbar'))
                ->tip($envTip),

            Field::make(t('Exceptions to Ignore', category: 'chrismou-rollbar'), Text::make('exceptionIgnoreList')
                ->monospace()
                ->textExpanderTriggers($envTriggers))
                ->instructions(t('Comma-separated list of fully qualified exception classes that should never be reported. Subclasses are ignored too.', category: 'chrismou-rollbar'))
                ->tip($envTip),
        ]);
    }

    /**
     * Summarises, in plain language, where the access token can live and what each choice means.
     */
    private function tokenSummaryCallout(): Callout
    {
        if ($this->app->make(RollbarChannel::class)->hasEnvToken()) {
            return Callout::make('chrismou-rollbar-token-summary', implode("\n", [
                t('**Rollbar is reporting from the moment your site starts.**', category: 'chrismou-rollbar'),
                '',
                t('Your access token is read from the `ROLLBAR_TOKEN` variable in your `.env` file, so the Access Token field below is not used.', category: 'chrismou-rollbar'),
            ]))->variant('success');
        }

        return Callout::make('chrismou-rollbar-token-summary', implode("\n", [
            t('**There are two places your Rollbar access token can live:**', category: 'chrismou-rollbar'),
            '',
            '- '.t('**In your `.env` file, as `ROLLBAR_TOKEN` (recommended).** Rollbar starts reporting the moment your site starts, so it also catches problems that stop Craft from loading, like the database being down.', category: 'chrismou-rollbar'),
            '- '.t('**In the Access Token field below.** Simpler to set up, but anything that goes wrong before Craft has finished loading is not reported.', category: 'chrismou-rollbar'),
            '',
            t('Using a different variable name, like `ROLLBAR_ACCESS_TOKEN`? Rename it to `ROLLBAR_TOKEN` in your `.env` file and you get the recommended setup.', category: 'chrismou-rollbar'),
        ]));
    }
}
