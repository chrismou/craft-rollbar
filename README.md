<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Rollbar for Craft CMS"></p>

<h1 align="center">Rollbar plugin for Craft CMS</h1>

This plugin provides [Rollbar](https://rollbar.com) support for [Craft CMS](https://craftcms.com).

## Features

* PHP Exception reporting to Rollbar
* JS Exception reporting to Rollbar (optional)

## Requirements

This plugin requires Craft CMS 4 or later. For Craft 3 support, please use the 3.x branch.

## Installation

### Plugin Store

To install [Rollbar](https://rollbar.com/), navigate to the Plugin Store section of your Craft control panel, search for `Rollbar`, and click the Install button.

### Composer

You can also add the package to your project using Composer.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require chrismou/craft-rollbar

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Rollbar.

## Configuration

First you'll need to setup a [Rollbar account](https://rollbar.com/).

Once you have an account and created your project, you'll be provided with an access Key. Navigate to Settings > Plugins > Rollbar 
and start typing the name of your environment variable in the `Access Token` field, until you see your variable name in the autocomplete box.

In order to log JS exceptions, you'll also need to enable the option and add your `postClientItemAccessToken` using the 
same method as above.

In order to exclude certain exceptions from being logged, create a comma separated list of fully qualified exception classes
and add it to the relevant setting using the same method as above.

For example, if you're looking to exclude 404 exceptions, you could add the following to your .env:

```
ROLLBAR_IGNORED_EXCEPTIONS=yii\web\NotFoundHttpException
```
Then simply add the `ROLLBAR_IGNORED_EXCEPTIONS` value to your settings.

## Licensing

Released under the MIT License. See LICENSE.

----

This plugin is maintained by [Chris Chrisostomou](https://mou.me). With thanks to the original developer, [Newism](https://newism.com.au).

