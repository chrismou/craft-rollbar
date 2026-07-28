<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Rollbar for Craft CMS"></p>

<h1 align="center">Rollbar plugin for Craft CMS</h1>

This plugin provides [Rollbar](https://rollbar.com) support for [Craft CMS](https://craftcms.com).

## Features

* PHP Exception reporting to Rollbar
* JS Exception reporting to Rollbar (optional)

## Compatibility

| Plugin version / branch | Craft | PHP | Notes |
|---|---|---|---|
| `3.x` (v3.0.x) | 3.5+ | - | |
| `4.x` (v4.0.x) | 4.x | 8.0.2-8.4 | |
| `5.x` (v5.0.x) | 5.x | 8.2-8.4 | current stable; Craft 5 is LTS |
| `6.x` (untagged, `dev-6.x`) | 6.x alpha | 8.5 | experimental; adapter-based; no release until Craft 6 beta |

## Requirements

This branch (`6.x`) requires Craft CMS 6 (alpha) and PHP 8.5. For Craft 5 support (current stable), use the `5.x` branch. For Craft 3 or 4 support, use the `3.x` or `4.x` branches.

For Craft 6 support, see the [Craft 6 (experimental)](#craft-6-experimental) section below.

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

## Craft 6 (experimental)

The `6.x` branch targets Craft 6 (alpha) via the official `craftcms/yii2-adapter` compatibility layer. It is untagged and should be treated as experimental.

**Requirements:** PHP 8.5, Craft 6.x alpha.

**Install:**

Add the `6.x` branch as a dev dependency. Either lower your project's `minimum-stability` to `dev` and require the branch:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require": {
        "chrismou/craft-rollbar": "dev-6.x"
    }
}
```

Or use an inline stability flag:

```
composer require chrismou/craft-rollbar:dev-6.x@dev
```

**Known behaviour difference (unconfirmed):** Under Craft 6, the JS error snippet is expected to reach front-end Twig templates only and not appear on Craft CP pages, because the rebuilt CP no longer renders through the legacy Twig View pipeline. This has not yet been confirmed against a live Craft 6 install.

No tagged release will be made until Craft 6 reaches beta. Until then, install via the dev constraint above.

## Licensing

Released under the MIT License. See LICENSE.

----

This plugin is maintained by [Chris Chrisostomou](https://mou.me). With thanks to the original developer, [Newism](https://newism.com.au).

