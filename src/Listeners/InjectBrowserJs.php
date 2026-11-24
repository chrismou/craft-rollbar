<?php

declare(strict_types=1);

namespace Chrismou\Rollbar\Listeners;

use Chrismou\Rollbar\Settings;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Foundation\Application;
use Rollbar\RollbarJsHelper;

/**
 * Adds the Rollbar browser JS snippet to the head of front-end page templates.
 */
final readonly class InjectBrowserJs
{
    public function __construct(
        private Application $app,
        private Settings $settings,
    ) {}

    public function handle(PageTemplateRendering $event): void
    {
        if ($event->templateMode !== TemplateMode::Site) {
            return;
        }

        if (! $this->settings->isPluginEnabled() || ! $this->settings->enableJs) {
            return;
        }

        $postClientItemAccessToken = $this->settings->getPostClientItemAccessToken();

        if ($postClientItemAccessToken === null) {
            return;
        }

        $snippet = RollbarJsHelper::buildJs([
            'accessToken' => $postClientItemAccessToken,
            'captureUncaught' => true,
            'payload' => [
                'environment' => $this->app->environment(),
            ],
        ]);

        HtmlStack::html($snippet, Position::Head, 'chrismou-rollbar');
    }
}
