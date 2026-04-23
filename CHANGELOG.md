# Changelog

This file tracks the state of the `laravel-test` banco de pruebas and its submodule pointers. Package-level changelogs live in each submodule.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [12.0.3] - 2026-04-23

### Changed
- Bumps all five submodules to `v12.0.3`. See each submodule's `CHANGELOG.md` for specifics. Highlights:
  - `laravel-general-helper` v12.0.3: fixes two PHP 8 runtime bugs in the `purge:temp` command (SplFileInfo → string coercions on `pathinfo()` and `unlink()`), plus native return types on `PdfHelper` and hardened `fopen()` error handling.
  - `laravel-odoo-connector` v12.0.3: removes a stray `dd()` in the `test:odoo` command that killed the process before returning an exit code, and handles the "no data" path gracefully.
  - All 5 packages: minor native return-type additions on helpers / traits.

### Tests
- 143 tests, 314 assertions, all green (access-list 17, cronjobs 22, general-helper 60, mailing 18, odoo-connector 26).

## [12.0.2] - 2026-04-23

### Changed
- Bumps all five submodules to `v12.0.2`. See each submodule's `CHANGELOG.md` for specifics:
  - `laravel-general-helper` v12.0.2 **fixes a breaking-change regression from v12.0.1** where `ExcelHelper::$spreadsheet` was inadvertently tightened from dynamic-public to declared-protected. A new `getSpreadsheet()` getter restores external access. Callers must migrate from `$excel->spreadsheet->…` to `$excel->getSpreadsheet()->…`.
  - `laravel-odoo-connector` v12.0.2 renames the typoed `Database\Relelations\*` namespace to `Database\Relations\*`. Technically breaking for anyone importing those classes directly; these are internal driver plumbing and normal consumer code does not.
  - `laravel-access-list`, `laravel-cronjobs`, `laravel-general-helper`, `laravel-mailing` all enable `declare(strict_types=1);` across `src/`.
  - `laravel-cronjobs` and `laravel-mailing` clean up legacy `@return void` docblocks on migrations (replaced with native `: void` return types).

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
