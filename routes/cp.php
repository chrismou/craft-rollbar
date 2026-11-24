<?php

declare(strict_types=1);

use Chrismou\Rollbar\Http\Controllers\DismissTokenRecommendationController;
use Chrismou\Rollbar\Http\Controllers\TestMessageController;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use Illuminate\Support\Facades\Route;

Route::get('settings/plugins/chrismou-rollbar/test', TestMessageController::class)
    ->middleware(RequireAdmin::class)
    ->name('chrismou-rollbar.test-message');

Route::get('chrismou-rollbar/dismiss-token-recommendation', DismissTokenRecommendationController::class)
    ->middleware(RequireAdmin::class)
    ->name('chrismou-rollbar.dismiss-token-recommendation');
