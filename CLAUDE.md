# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Craft CMS plugin (`chrismou/craft-rollbar`) that reports PHP exceptions - and optionally JS exceptions - to Rollbar. It is a distributable Composer package, not an application: there is no test suite, no build step, no linter config, and no CI. The only local command that does anything is `composer install` / `composer validate`.

Because the package has no runnable entry point of its own, changes are verified inside a real Craft install. Add a path repository to a Craft project's `composer.json` pointing at this directory, `composer require chrismou/craft-rollbar:@dev`, install the plugin in the CP, then use the **Send test message to Rollbar** button on the settings screen (`settings/plugins/chrismou-rollbar`) to exercise the outbound path.

## Branch strategy

One long-lived branch per supported Craft major, each pinning its own dependency floor:

| Branch | craftcms/cms                   | craftcms/yii2-adapter | rollbar/rollbar | php            |
| ------ | ------------------------------ | --------------------- | --------------- | -------------- |
| `3.x`  | `^3.5.0`                       | -                     | `^2.0`          | -              |
| `4.x`  | `^4.0.0-alpha.1`               | -                     | `^3.0`          | `>=8.0.2 <9.0` |
| `5.x`  | `^5.0.0-alpha.1`               | -                     | `^4.0`          | `>=8.2 <9.0`   |
| `main` | mirrors `5.x` until Craft 6 GA | -                     | `^4.0`          | `>=8.2 <9.0`   |
| `6.x`  | `^6.0.0-alpha.1`               | `^6.0.0-alpha.1`      | `^4.0`          | `^8.5`         |

`6.x` targets Craft 6 (untagged, experimental) via the official `craftcms/yii2-adapter` compatibility layer. `main` mirrors `5.x` (current stable) until Craft 6 GA, at which point `main` will switch to merging from `6.x`. Fixes are made on the oldest affected branch and merged forward: `3.x` → `4.x` → `5.x` → `6.x`. Any fix landing on `5.x` is also merged to `main`. Source is otherwise near-identical across branches; the deltas are in `composer.json` and Craft API signature changes.

## Architecture

Everything is wired in `Plugin::init()` (`src/Plugin.php`) as Yii event listeners - there are no services, no migrations, and no database tables.

- **PHP reporting** - `ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION`. Registered only when `pluginEnabled` and `accessToken` both resolve truthy at boot, so toggling the plugin off costs nothing at runtime. `Rollbar::init()` is called lazily _inside_ the handler, not at boot; environment is always `App::env('CRAFT_ENVIRONMENT')`.
- **Ignore list** - `Settings::getExceptionsToIgnore()` yields lowercased, trimmed class names from a comma-separated string. The comparison in the handler lowercases `get_class($event->exception)`, so both sides must stay case-normalised.
- **JS reporting** - `View::EVENT_BEFORE_RENDER_TEMPLATE`, gated on `enableJs` + `postClientItemAccessToken`. Injects `RollbarJsHelper` output into `View::POS_HEAD`. On Craft 5 and earlier this fires for CP templates too. On Craft 6, the CP is rebuilt on Vue/Inertia and is expected to no longer render through the legacy Twig View pipeline, so the snippet is expected to reach front-end Twig templates only (unconfirmed pending test install).
- **Test route** - `UrlManager::EVENT_REGISTER_CP_URL_RULES` maps `settings/plugins/chrismou-rollbar/test` to `AdminController::actionTest()`, which requires the `admin` permission and sends an info-level message.

### The plugin handle is load-bearing

`chrismou-rollbar` (set in `composer.json` under `extra.handle`) is hardcoded across the codebase and must match exactly: the translation category, the template namespace (`chrismou-rollbar/settings`), the CP URL rules and redirect, the session flash key, and the project-level override file `config/chrismou-rollbar.php`. Renaming the handle means updating all of them.

The PSR-4 root is `chrismou\rollbar\` → `src/`, which gives Craft the alias `@chrismou/rollbar` used by `RollbarAsset::$sourcePath`.

### Settings and env vars

Every user-facing setting is env-parseable. `Settings::behaviors()` registers `EnvAttributeParserBehavior` over `pluginEnabled`, `accessToken`, `postClientItemAccessToken`, and `exceptionIgnoreList`, and the Twig fields use `suggestEnvVars` / `includeEnvVars` so admins type `$ROLLBAR_ACCESS_TOKEN` rather than a literal.

**Always read these through the getters** (`getAccessToken()`, `getPostClientItemAccessToken()`, `getPluginEnabled()`, `getExceptionsToIgnore()`) - they run `App::parseEnv()`. Reading the raw property gives you the literal `$VAR_NAME` string. `src/Plugin.php:75` currently reads `$this->settings->postClientItemAccessToken` directly while the guard on line 67 uses the getter, so a JS token set via env var is passed to `RollbarJsHelper` unparsed.

### Adding a setting

Four coordinated edits:

1. `src/models/Settings.php` - typed public property, entry in `rules()`, entry in the `behaviors()` attribute list if it should support env vars, and a `App::parseEnv()` getter.
2. `src/config.php` - the template admins copy to `config/chrismou-rollbar.php`, with a comment describing it.
3. `src/templates/settings.twig` - the CP field, passing `warning: macros.configWarning('<name>', 'chrismou-rollbar')`.
4. `src/Plugin.php` - consume it via the getter.

`macros.configWarning` reads `config/chrismou-rollbar.php` at render time and shows an "overridden by config file" notice, so the setting name must be spelled identically in all four places for that warning to work.

## Known quirks

- `composer.lock` is gitignored, so dependency resolution is unpinned for consumers by design.
