<?php


namespace chrismou\rollbar\controllers;


use craft\helpers\App;
use craft\web\Controller;
use chrismou\rollbar\Plugin;
use Rollbar\Rollbar;

class AdminController extends Controller
{
    public function actionTest()
    {
        $this->requirePermission('admin');

        $accessToken = Plugin::getInstance()->getSettings()->getAccessToken();
        if (!empty($accessToken)) {
            Rollbar::init(
                [
                    'access_token' => $accessToken,
                    'environment' => App::env('CRAFT_ENVIRONMENT'),
                ]
            );
            Rollbar::info('test message from craft-rollbar');
        }
        \Craft::$app->session->setFlash('chrismou-rollbar-setting', 'Sent test message.');
        return $this->redirect('/admin/settings/plugins/chrismou-rollbar');
    }
}