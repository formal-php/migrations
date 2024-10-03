---
hide:
    - navigation
---

# SQL

## Write your migrations in files

The simplest way to write migrations is in plain SQL files.

With this strategy all your files must be in the same folder and will be sorted alphabetically for a guarantee of execution order.

You can use any naming strategy but here's a good starting point:

```
migrations/
    2024_10_03_some_feature.sql
    2024_10_03_concurrent_feature.sql
    2024_10_11_another_one.sql
```

??? tip
    These migrations whould be executed in this order:

    - `2024_10_03_concurrent_feature.sql`
    - `2024_10_03_some_feature.sql`
    - `2024_10_11_another_one.sql`

Each query inside a file must be separated by a line starting with `--` (1). A query can be written on multiple lines.
{ .annotate }

1. Which the SQL symbol for comments. This allows to write comments in your file.

You can then run them via a script like this one:

```php title="migrate.php"
<?php

use Formal\Migrations\Factory;
use Innmind\OperatingSystem\Factory as OS;
use Innmind\Url\Url;

require 'path/to/composer/autoload.php';

$dsn = Url::of('mysql://user:pwd@127.0.0.1:3306/database');

Factory::of(OS::build())
    ->storeVersionsInDatabase($dsn)
    ->sql()
    ->files(Path::of('path/to/migrations/'))
    ->migrate($dsn)
    ->match(
        static fn() => print('Everything has been migrated'),
        static fn(\Throwable $error) => printf(
            'Migrations failed with the message : %s',
            $error->getMessage(),
        ),
    );
```

The `$dsn` can be any value supported by [`formal/access-layer`](https://formal-php.github.io/access-layer/).

Then you can add `#!sh php migrate.php` in your deploy process.

??? info
    Here the versions are stored in the database being migrated, but you use different databases if you want.

    Alternatively you can store versions on the filesystem by using `storeVersionsOnFilesystem()` instead of `storeVersionsInDatabase()`.
