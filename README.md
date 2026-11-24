<p align="center"><img src="./resources/icon.svg" width="100" height="100" alt="Rollbar for Craft CMS"></p>

<h1 align="center">Rollbar plugin for Craft CMS</h1>

This plugin provides [Rollbar](https://rollbar.com) support for [Craft CMS](https://craftcms.com).

## Features

* PHP exception and error-log reporting to Rollbar, with Craft context (Craft version, edition, site, request type and the logged-in user)
* JS exception reporting to Rollbar on front-end templates (optional)
* Configured from the control panel, with environment variable support - no config files to edit

## Compatibility

| Plugin version / branch | Craft | PHP | Notes |
|---|---|---|---|
| `3.x` (v3.0.x) | 3.5+ | - | |
| `4.x` (v4.0.x) | 4.x | 8.0.2-8.4 | |
| `5.x` (v5.0.x) | 5.x | 8.2-8.4 | current stable; Craft 5 is LTS |
| `6.x` (untagged, `dev-6.x`) | 6.x beta | 8.5 | native Craft 6 plugin, built on `rollbar/rollbar-laravel` |

## Requirements

This branch (`6.x`) requires Craft CMS 6 (beta) and PHP 8.5. For Craft 5 support (current stable), use the `5.x` branch. For Craft 3 or 4 support, use the `3.x` or `4.x` branches.

## Installation

The `6.x` branch is untagged while Craft 6 is in beta, so require the branch:

```
composer require chrismou/craft-rollbar:dev-6.x@dev
php artisan craft:plugin:install chrismou-rollbar
```

You can also install it from Settings → Plugins in the control panel once the package is required.

## Configuration

First you'll need to set up a [Rollbar account](https://rollbar.com/) and create a project, which gives you its access tokens.

### Where to put your access token

There are two places your `post_server_item` access token can live:

* **In your `.env` file, as `ROLLBAR_TOKEN` (recommended).** Rollbar starts reporting the moment your site starts, so it also catches problems that stop Craft from loading, like the database being down. You don't need to enter anything in the plugin's Access Token field.
* **In the Access Token field in the plugin's settings.** Simpler to set up, but anything that goes wrong before Craft has finished loading is not reported. The field accepts a literal token or any other environment variable (start typing `$`).

If both are set, `ROLLBAR_TOKEN` wins. The settings screen tells you which one is in use.

If you're upgrading and your variable has a different name, such as `ROLLBAR_ACCESS_TOKEN`, rename it to `ROLLBAR_TOKEN` to get the recommended setup. Admins see a control panel alert suggesting this until they dismiss it.

With `ROLLBAR_TOKEN` set, early reporting runs even if the plugin is uninstalled in Craft, because it starts before Craft does. To switch it off, remove the variable or set `ROLLBAR_LEVEL=none`. Disabling the plugin in its settings stops reporting from the point Craft has loaded.

### Other settings

Navigate to Settings → Plugins → Rollbar:

* **Minimum Log Level** - exceptions and log messages at this level or above are reported. Defaults to `error`. A `ROLLBAR_LEVEL` environment variable overrides it, and also applies before Craft has loaded.
* **Enable Rollbar JS integration** and **Post Client Item Access Token** - adds the Rollbar browser snippet to the `<head>` of front-end templates. The control panel itself is not instrumented.
* **Exceptions to Ignore** - a comma-separated list of fully qualified exception classes that should never be reported. Subclasses are ignored too.

Use the **Send test message to Rollbar** link on the settings screen to confirm the token works.

To keep settings out of the project config, publish the config file and set them there instead - anything in `config/craft/chrismou-rollbar.php` overrides the stored settings:

```
php artisan vendor:publish --tag=chrismou-rollbar
```

### How reporting works

Craft 6 is a Laravel application, so the plugin reports through [`rollbar/rollbar-laravel`](https://github.com/rollbar/rollbar-php-laravel). It defines a `rollbar` log channel from the plugin's settings and adds it alongside your default log channel, which means anything Laravel reports or logs at the configured level reaches Rollbar: web requests, console commands and queue jobs. Exceptions Laravel doesn't report (404s and other HTTP exceptions, validation failures, and so on) are not sent.

Reports sent before Craft has loaded are plain Laravel reports; the Craft context (user, site, edition) is added from the point Craft has loaded.

If your project defines its own `logging.channels.rollbar` entry, those values take precedence over the plugin's settings.

### Upgrading from Craft 5

Existing settings carry over. Two behaviour changes to be aware of:

* The ignore list now matches subclasses as well as exact classes, and Yii exception classes (for example `yii\web\NotFoundHttpException`) no longer exist - 404s are no longer reported by default, so most ignore lists can simply be emptied.
* Log messages at the configured level are reported as well as exceptions.

## Licensing

Released under the MIT License. See LICENSE.

----

This plugin is maintained by [Chris Chrisostomou](https://mou.me). With thanks to the original developer, [Newism](https://newism.com.au).

