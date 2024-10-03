---
hide:
    - navigation
    - toc
---

# Getting started

This library is a simple one way migration system.

You can run both SQL and Commands migrations. You can choose to store which migrations have been run either in a database or on the filesystem.

## Installation

```sh
composer require formal/migrations
```

## Basic usage

```php
use Formal\Migrations\Factory;
use Innmind\OperatingSystem\Factory as OS;
use Innmind\Url\{
    Url,
    Path,
};

$os = OS::build();

Factory::of($os)
    ->storeVersionsOnFilesystem(Path::of('/some/folder/'))
    ->sql()
    ->files(Path::of('/path/to/sql/migrations/'))
    ->migrate(Url::of('mysql://user:pwd@127.0.0.1:3306/database'))
    ->match(
        static fn() => print('Everything has been migrated'),
        static fn(\Throwable $error) => print($error->getMessage()),
    );
```
