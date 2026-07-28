# Rollbar Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased (6.x)

- Added Craft 6 support via `craftcms/yii2-adapter` (adapter-based compatibility build; all existing Yii event APIs are preserved by the adapter)
- PHP minimum version raised to 8.5 (required by Craft 6)
- JS error snippet expected not to appear on Craft CP pages under Craft 6 (unconfirmed pending test install); front-end Twig template injection unchanged

## 3.0.0 - 2024-08-09
- Ported from `newism/craft-rollbar`
- Added .env based setting support

