<?php

declare(strict_types=1);

namespace Chrismou\Rollbar\Http\Controllers;

use Chrismou\Rollbar\Listeners\RecommendEnvToken;
use CraftCms\Cms\User\Users;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DismissTokenRecommendationController
{
    /**
     * Permanently hides the `ROLLBAR_TOKEN` recommendation alert for the current user.
     */
    public function __invoke(Request $request, Users $users): RedirectResponse
    {
        $users->shunMessageForUser($request->craftUser()->getCraftUserId(), RecommendEnvToken::DISMISSED_MESSAGE);

        return back();
    }
}
