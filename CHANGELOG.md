# Rollbar Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.0.2 - 2026-07-28
### Fixed
- JS client-item access token set as an environment variable is now resolved before being passed to the Rollbar JS snippet; previously the literal `$VAR` reference was emitted, silently breaking client-side error reporting.

## 3.0.0 - 2024-08-09
- Ported from `newism/craft-rollbar`
- Added .env based setting support

