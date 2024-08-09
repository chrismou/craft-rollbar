<?php

namespace chrismou\rollbar\models;

use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use craft\base\Model;

class Settings extends Model
{
    public string $accessToken = '';
    public bool $enableJs = false;
    public string $postClientItemAccessToken = '';
    public string $exceptionIgnoreList = '';

    public bool $pluginEnabled = true;

    public function rules(): array
    {
        return [
            ['pluginEnabled', 'boolean'],
            ['accessToken', 'string'],
            ['accessToken', 'required'],
            ['postClientItemAccessToken', 'string'],
            ['postClientItemAccessToken', 'default', 'value' => ''],
            ['exceptionIgnoreList', 'default', 'value' => ''],
        ];
    }

    public function behaviors() :array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['pluginEnabled', 'accessToken', 'postClientItemAccessToken', 'exceptionIgnoreList'],
            ],
        ];
    }

    public function getPluginEnabled(): string
    {
        return App::parseEnv($this->pluginEnabled);
    }

    public function getAccessToken(): string
    {
        return App::parseEnv($this->accessToken);
    }

    public function getPostClientItemAccessToken(): string
    {
        return App::parseEnv($this->postClientItemAccessToken);
    }

    public function getExceptionsToIgnore() : \Traversable
    {
        $exceptionIgnoreList = App::parseEnv($this->exceptionIgnoreList);

        if($exceptionIgnoreList !== null && $exceptionIgnoreList !== '')
        {
            foreach(explode(",", $exceptionIgnoreList) as $exception)
            {
                yield trim(strtolower($exception));
            }
        }
    }
}
