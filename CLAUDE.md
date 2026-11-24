# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Craft CMS plugin (`chrismou/craft-rollbar`) that reports PHP exceptions and error logs - and optionally JS exceptions - to Rollbar. It is a distributable Composer package, not an application: there is no test suite, no build step, no linter config, and no CI. The only local command that does anything is `composer install` / `composer validate`.

Because the package has no runnable entry point of its own, changes are verified inside a real Craft install. Add a path repository to a Craft project's `composer.json` pointing at this directory, `composer require chrismou/craft-rollbar:@dev`, run `php artisan craft:plugin:install chrismou-rollbar`, then use the **Send test message to Rollbar** link on the settings screen (`settings/plugins/chrismou-rollbar`) to exercise the outbound path. The Craft project's own `vendor/bin/pint` can be pointed at this directory to check code style.

## Branch strategy

One long-lived branch per supported Craft major, each pinning its own dependency floor:

| Branch | craftcms/cms                   | Rollbar package                | php            |
| ------ | ------------------------------ | ------------------------------ | -------------- |
| `3.x`  | `^3.5.0`                       | `rollbar/rollbar ^2.0`         | -              |
| `4.x`  | `^4.0.0-alpha.1`               | `rollbar/rollbar ^3.0`         | `>=8.0.2 <9.0` |
| `5.x`  | `^5.0.0-alpha.1`               | `rollbar/rollbar ^4.0`         | `>=8.2 <9.0`   |
| `main` | mirrors `5.x` until Craft 6 GA | `rollbar/rollbar ^4.0`         | `>=8.2 <9.0`   |
| `6.x`  | `^6.0.0-beta.1`                | `rollbar/rollbar-laravel ^8.1` | `^8.5`         |

`6.x` is a native Craft 6 (Laravel) plugin and shares no source with the Yii-based branches, so fixes on `3.x`-`5.x` are **not** merged forward into `6.x` - port them by hand if they still apply. `3.x` → `4.x` → `5.x` still merge forward as before, and any fix landing on `5.x` is also merged to `main`. `main` mirrors `5.x` (current stable) until Craft 6 GA, at which point it switches to following `6.x`. `6.x` stays untagged while Craft 6 is in beta.

`6.x-compatibility` preserves the earlier adapter-based Craft 6 build (`craftcms/yii2-adapter` + `rollbar/rollbar ^4.0`) that `6.x` held before the native rewrite. It is kept for reference only.

## Architecture (6.x)

The plugin is a thin Craft layer over `rollbar/rollbar-laravel`. There are no migrations and no database tables.

- **`src/EarlyReportingServiceProvider.php`** - a plain Laravel provider, auto-discovered via `extra.laravel.providers`, so it runs before Craft touches the database and regardless of whether the plugin is installed. It binds `RollbarChannel` as a singleton and, when `ROLLBAR_TOKEN` is set, registers the channel straight away. This is what gets a database outage reported.
- **`src/Plugin.php`** - the plugin class, which in Craft 6 is itself a Laravel service provider (and must NOT be listed under `extra.laravel.providers` - only the early provider is). `register()` re-registers the channel with the plugin's settings (or calls `deactivate()` when the plugin is disabled), `boot()` adds the Craft context, `settingsUi()` builds the settings screen from Craft's `Ui` nodes - there are no Twig templates. The callout at the top of the settings screen explains the two token options in plain language.
- **`src/Logging/RollbarChannel.php`** - all of the Rollbar wiring, shared by both providers:
  - `register()` writes `logging.channels.rollbar` (values the project defined there itself are captured on the first call and always win), force-registers `RollbarServiceProvider` because rollbar-laravel's auto-discovered provider bails out when it runs before a token exists, points `logging.default` at a `chrismou-rollbar` stack channel wrapping the previous default plus `rollbar`, and sets the handler's level. It is safe to call twice.
  - `deactivate()` restores the original default log channel.
  - `applyCraftContext()` adds `person_fn`, `custom_data_method` and (when an ignore list is set) `check_ignore` to the logger, skipping any the project configured itself.
  - `isActive()` is true when a `RollbarLogger` is bound; `hasEnvToken()` is true when `ROLLBAR_TOKEN` is set.
- **`src/Listeners/InjectBrowserJs.php`** - listens for `PageTemplateRendering` and pushes the `RollbarJsHelper` snippet onto the `HtmlStack` head, for `TemplateMode::Site` only. The control panel (Inertia) is not instrumented.
- **`src/Listeners/RecommendEnvToken.php`** - listens for `CpAlertsResolving` and adds an alert for admins whose token is in the plugin's settings rather than `ROLLBAR_TOKEN`. Dismissal is permanent and per user, stored with Craft's `Users::shunMessageForUser()` (no expiry) by `DismissTokenRecommendationController`. Craft's alert renderer drops non-ASCII link text, so the dismiss link is a word, not an icon.
- **`src/Http/Controllers/TestMessageController.php`** + **`routes/cp.php`** - the admin-only test message route. It reports straight to the `RollbarLogger` so the log level doesn't filter it, and flashes whether Rollbar accepted it.

`rollbar-laravel` reads `ROLLBAR_TOKEN` and `ROLLBAR_LEVEL` from the env itself and prefers them over config, so both override the plugin's settings; `ROLLBAR_LEVEL=none` switches everything off.

Reporting rides on Laravel's exception handler and log channels, so anything Laravel doesn't report (HTTP exceptions, validation failures) never reaches Rollbar, and log messages at or above `logLevel` do.

### The plugin handle is load-bearing

`chrismou-rollbar` (set in `composer.json` under `extra.handle`) is hardcoded across the codebase and must match exactly: the translation category, the CP route and redirect in `routes/cp.php` / `TestMessageController`, the `HtmlStack` key, the stack channel name, the `vendor:publish` tag, and the override file `config/craft/chrismou-rollbar.php` (published from `config/chrismou-rollbar.php`). Renaming the handle means updating all of them.

The PSR-4 root is `Chrismou\Rollbar\` → `src/`. Craft finds `resources/icon.svg`, `routes/`, `lang/` and `config/` by convention relative to the package root.

### Settings and env vars

`pluginEnabled`, `accessToken`, `postClientItemAccessToken` and `exceptionIgnoreList` are env-parseable: the stored value is either a literal or a `$ENV_VAR` reference.

**Always read these through the getters** (`isPluginEnabled()`, `getAccessToken()`, `getPostClientItemAccessToken()`, `getExceptionsToIgnore()`) - they run `Env::parse()`. Reading the raw property gives you the literal `$VAR_NAME` string.

`pluginEnabled` is a `BooleanMenu`, whose options are `'1'`, `'0'` or an env var reference, so `Settings::validationData()` normalises it to that form for both the settings screen and the stored project config.

The setting names are unchanged from the Craft 5 plugin so stored settings survive the upgrade - don't rename them.

### Adding a setting

1. `src/Settings.php` - typed public property, a rule in `getRules()`, and an `Env::parse()` getter if it should support env vars.
2. `config/chrismou-rollbar.php` - a commented-out example with a description.
3. `src/Plugin.php` - a field in `settingsUi()`.
4. Consume it via the getter (usually in `RollbarChannel`).

## Known quirks

- `composer.lock` is gitignored, so dependency resolution is unpinned for consumers by design.
- Plugin settings load from the database, so errors thrown before Craft loads its plugins are only reported when the token is in `ROLLBAR_TOKEN`.
- After changing `composer.json` (for example `extra.laravel.providers`), a Craft project using this package through a path repository only picks the change up if the repository has `"options": {"reference": "config"}`, followed by `composer update chrismou/craft-rollbar`.
- `rollbar-laravel`'s Monolog handler attaches the session contents to the reported person; Rollbar's default scrub fields apply.
