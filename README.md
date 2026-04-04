# migrations

[![CI](https://github.com/formal-php/migrations/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/formal-php/migrations/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/formal-php/migrations/branch/develop/graph/badge.svg)](https://codecov.io/gh/formal-php/migrations)
[![Type Coverage](https://shepherd.dev/github/formal-php/migrations/coverage.svg)](https://shepherd.dev/github/formal-php/migrations)

This library is a simple one way migration system.

You can run both SQL and Commands migrations.

## Installation

```sh
composer require formal/migrations
```

## Usage

```php
use Formal\Migrations\{
    Factory,
    Failure,
}
use Innmind\OperatingSystem\Factory as OS;
use Innmind\Url\{
    Url,
    Path,
};

$dsn = Url::of('mysql://user:pwd@127.0.0.1:3306/database');

Factory::of(OS::build())
    ->storeVersionsInDatabase($dsn)
    ->unwrap()
    ->sql()
    ->files(Path::of('migrations/folder/'))
    ->migrate($dsn)
    ->match(
        static fn() => print('Everything has been migrated'),
        static fn(Failure $failure) => printf(
            'Migrations failed with the message : %s',
            $failure->error()->getMessage(),
        ),
    );
```

## Documentation

Full documentation available [here](https://formal-php.github.io/migrations/).
