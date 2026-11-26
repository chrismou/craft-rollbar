# Rollbar Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 4.0.3 - 2026-11-26
### Fixed
- The "Send test message" action now redirects back to the plugin settings using the configured control panel trigger; previously the hardcoded `/admin` path caused a 404 on sites with a custom `cpTrigger`.

## 4.0.2 - 2026-07-28
### Fixed
- JS client-item access token set as an environment variable is now resolved before being passed to the Rollbar JS snippet; previously the literal `$VAR` reference was emitted, silently breaking client-side error reporting.

## 3.0.0 - 2024-08-09
- Ported from `newism/craft-rollbar`
- Added .env based setting support

