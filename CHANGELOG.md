# Rollbar Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased (6.x)

- Rewritten as a native Craft 6 plugin - `craftcms/yii2-adapter` is no longer required
- Reporting now goes through `rollbar/rollbar-laravel`'s log channel, so console commands, queue jobs and log messages are reported as well as web exceptions
- Added early reporting: with the access token in a `ROLLBAR_TOKEN` environment variable, reporting starts before Craft boots, so failures such as a database outage are reported
- Added a dismissible control panel alert for admins recommending `ROLLBAR_TOKEN`, and a plain-language summary of the token options at the top of the settings screen
- Added a Minimum Log Level setting (defaults to `error`)
- Added Craft context to reports: Craft version, edition, site, request type and the logged-in user
- The ignore list now also matches subclasses of the listed exceptions
- The test message now reports whether Rollbar accepted it
- The JS snippet is only added to front-end templates, and only while the plugin is enabled
- The config override file moved to `config/craft/chrismou-rollbar.php`
- PHP minimum version raised to 8.5 (required by Craft 6)

## 3.0.0 - 2024-08-09
- Ported from `newism/craft-rollbar`
- Added .env based setting support

