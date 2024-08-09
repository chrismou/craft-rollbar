<?php

namespace chrismou\rollbar;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\ExceptionEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\helpers\App;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use craft\web\View;
use chrismou\rollbar\models\Settings;
use Rollbar\Rollbar;
use Rollbar\RollbarJsHelper;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public static $plugin;

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Craft::info(
            Craft::t(
                'chrismou-rollbar',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        if ($this->settings->accessToken) {
            Event::on(
                ErrorHandler::class,
                ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
                function (ExceptionEvent $event) {
                    //check to see if this exception type is in our ignore list
                    $ignoreList = iterator_to_array($this->settings->getExceptionsToIgnore());
                    if(in_array(strtolower(get_class($event->exception)), $ignoreList)) {
                        return;
                    }

                    Rollbar::init(
                        [
                            'access_token' => $this->settings->getAccessToken(),
                            'environment' => App::env('CRAFT_ENVIRONMENT'),
                        ]
                    );
                    Rollbar::error($event->exception);
                }
            );
        }

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['settings/plugins/chrismou-rollbar/test'] = 'chrismou-rollbar/admin/test';
            }
        );

        if($this->settings->enableJs && $this->settings->getPostClientItemAccessToken()) {
            // Load JS before template is rendered
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                function (TemplateEvent $event) {
                    $view = Craft::$app->getView();
                    $rollbarJsHelper = new RollbarJsHelper([
                        'accessToken' => $this->settings->postClientItemAccessToken,
                        'captureUncaught' => true,
                        'payload' => [
                            'environment' => App::env('CRAFT_ENVIRONMENT'),
                        ],
                    ]);
                    $rollbarJs = $rollbarJsHelper->configJsTag() . $rollbarJsHelper->jsSnippet();
                    $view->registerJs($rollbarJs, View::POS_HEAD);
                }
            );
        }
    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'chrismou-rollbar/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
