<?php

declare(strict_types=1);

namespace Chrismou\Rollbar\Http\Controllers;

use Chrismou\Rollbar\Logging\RollbarChannel;
use CraftCms\Cms\Http\RespondsWithFlash;
use Rollbar\Payload\Level;
use Rollbar\RollbarLogger;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

final class TestMessageController
{
    use RespondsWithFlash;

    /**
     * Sends an info-level test message to Rollbar and reports back whether Rollbar accepted it.
     */
    public function __invoke(RollbarChannel $rollbarChannel): Response
    {
        if (! $rollbarChannel->isActive()) {
            return $this->asFailure(t('Rollbar is not configured - add an access token and enable the plugin first.', category: 'chrismou-rollbar'));
        }

        // Sent straight to the logger so the test isn't filtered out by the configured log level
        $rollbarResponse = app(RollbarLogger::class)->report(Level::INFO, 'Test message from craft-rollbar');

        if (! $rollbarResponse->wasSuccessful()) {
            return $this->asFailure(t('Rollbar rejected the test message (status {status}).', [
                'status' => $rollbarResponse->getStatus(),
            ], 'chrismou-rollbar'));
        }

        return $this->asSuccess(t('Test message sent to Rollbar.', category: 'chrismou-rollbar'), redirect: cp_url('settings/plugins/chrismou-rollbar'));
    }
}
