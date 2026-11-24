<?php

declare(strict_types=1);

namespace Chrismou\Rollbar\Listeners;

use Chrismou\Rollbar\Logging\RollbarChannel;
use Chrismou\Rollbar\Settings;
use CraftCms\Cms\Cp\Events\CpAlertsResolving;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Users;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * Shows admins a control panel alert recommending the `ROLLBAR_TOKEN` env var, until they dismiss it.
 */
final readonly class RecommendEnvToken
{
    /** The key the dismissal is stored under, per user. */
    public const string DISMISSED_MESSAGE = 'chrismou-rollbar:recommend-env-token';

    public function __construct(
        private Settings $settings,
        private RollbarChannel $rollbarChannel,
        private Users $users,
    ) {}

    public function handle(CpAlertsResolving $event): void
    {
        if (! $this->shouldRecommendEnvToken()) {
            return;
        }

        $event->alerts[] = implode(' ', [
            Html::encode(t('We recommend changing how your Rollbar access token is configured.', category: 'chrismou-rollbar')),
            Html::a(t('Learn more', category: 'chrismou-rollbar'), cp_url('settings/plugins/chrismou-rollbar'), ['class' => 'go']),
            // Craft's alert renderer drops non-ASCII link text, so this can't be a ✕ icon
            Html::a(t('Dismiss', category: 'chrismou-rollbar'), cp_url('chrismou-rollbar/dismiss-token-recommendation'), [
                'title' => t('Don\'t show this again', category: 'chrismou-rollbar'),
            ]),
        ]);
    }

    /**
     * Only worth showing to an admin whose token is set in the plugin's settings rather than `ROLLBAR_TOKEN`.
     */
    private function shouldRecommendEnvToken(): bool
    {
        $user = currentUser();
        $userId = $user?->getCraftUserId();

        if ($userId === null || ! $user->isAdmin()) {
            return false;
        }

        if (! $this->settings->isPluginEnabled() || $this->rollbarChannel->hasEnvToken()) {
            return false;
        }

        if ($this->settings->getAccessToken() === null) {
            return false;
        }

        return ! $this->users->hasUserShunnedMessage($userId, self::DISMISSED_MESSAGE);
    }
}
