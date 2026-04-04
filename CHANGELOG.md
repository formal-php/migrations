# Changelog

## [Unreleased]

### Added

- `Formal\Migrations\Failure`
- `Formal\Migrations\Applied::versions()`

### Changed

- Requires PHP `8.4`
- Requires `formal/orm:~6.0`
- Requires `innmind/foundation:~2.1`
- `Formal\Migrations\SQL` has been renamed to `SQL\Runner` and is now flagged as internal
- `Formal\Migrations\Commands` has been renamed to `Commands\Runner` and is now flagged as internal
- `Formal\Migrations\Factory\SQL::migrate()` now returns an `Innmind\Immutable\Either<Formal\Migrations\Failure, Formal\Migrations\Applied>`
- `Formal\Migrations\Factory\Commands::migrate()` now returns an `Innmind\Immutable\Either<Formal\Migrations\Failure, Formal\Migrations\Applied>`

### Removed

- `Formal\Migrations\Runner`
- `Formal\Migrations\Migration`
- `Formal\Migrations\Applied::match()`

## 1.1.0 - 2025-02-28

### Added

- Support for `formal/orm` `4`
- The versions table can be modified in `Factory::storeVersionsInDatabase()` via its second parameter

## 1.0.0 - 2024-10-04

### Added

- `Formal\Migrations\Factory`
- `Formal\Migrations\SQL`
- `Formal\Migrations\SQL\Migration`
- `Formal\Migrations\SQL\Load`
- `Formal\Migrations\Commands`
- `Formal\Migrations\Commands\Migration`
- `Formal\Migrations\Commands\Reference`
