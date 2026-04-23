# Changelog

This file tracks the state of the `laravel-test` banco de pruebas and its submodule pointers. Package-level changelogs live in each submodule.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [12.0.1] - 2026-04-23

### Changed
- Bumps all five submodules to `v12.0.1`. See each submodule's `CHANGELOG.md` for the specific changes.
- Adds project-level `LICENSE` and `CHANGELOG.md`.

## [12.0] - 2026-04-23

The Laravel 12 branch of the test harness. Replaces the prior `master` branch (now retired) with a version-named default, matching the major-aligned branching policy adopted across the submodules.

### Added
- All five submodules migrated to Laravel 12 (`v12.0.0` tags).
- Root `README.md` explaining the harness, branch model, and workflow.
- Root `CLAUDE.md` documenting breaking-change notes, Testbench setup, and known pendings for AI collaborators and maintainers.
- Sail-based development environment pinned to PHP 8.4 runtime.

### Changed
- Legacy `master` branch of the harness renamed to `9.x` and frozen for historical reference; Laravel 12 work lives on the `12.0` branch.

## Earlier history

Prior iterations of the harness lived on the (now-retired) `master` branch and in the version-specific branches `9.0` and `11.0`. Those remain on GitHub for reference but are not updated.
