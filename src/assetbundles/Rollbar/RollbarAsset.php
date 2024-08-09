<?php

namespace chrismou\rollbar\assetbundles\Rollbar;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class RollbarAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@chrismou/rollbar/assetbundles/Rollbar/dist';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/Rollbar.css',
        ];

        parent::init();
    }
}
