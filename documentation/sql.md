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
declare(strict_types = 1);

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

## Write your migrations in PHP

```php title="migrate.php"
<?php
declare(strict_types = 1);

use Formal\Migrations\{
    Factory,
    SQL\Migration,
};
use Formal\AccessLayer\Query;
use Innmind\OperatingSystem\Factory as OS;
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

require 'path/to/composer/autoload.php';

$dsn = Url::of('mysql://user:pwd@127.0.0.1:3306/database');

Factory::of(OS::build())
    ->storeVersionsInDatabase($dsn)
    ->sql()
    ->of(Sequence::of(
        Migration::of(
            'some feature',
            Query\SQL::of('CREATE TABLE `some_feature` (`value` INT NOT NULL);'),
        ),
        Migration::of(
            'another feature',
            Query\SQL::of('CREATE TABLE `another_feature` (`value` INT NOT NULL);'),
        ),
    ))
    ->migrate($dsn)
    ->match(
        static fn() => print('Everything has been migrated'),
        static fn(\Throwable $error) => printf(
            'Migrations failed with the message : %s',
            $error->getMessage(),
        ),
    );
```

Then you can add `#!sh php migrate.php` in your deploy process.

Here the migrations are always run in the order you specify. Even though the migration name `another feature` comes first alphabetically it still executed last. This is intended so you don't need to think too much about the execution order when specifying these names.

!!! warning ""
    Your migrations name still MUST be unique. Otherwise some won't be run.

In the example above there's only one `Query\SQL` query per migration but you can add multiple ones. And they can be any instance of the `Formal\AccessLayer\Query` interface.

??? tip
    The above example defines the migrations in the same file as the script. This will quickly become a large file. Instead you should split your migrations by features like this:

    === "Script"
        ```php title="migrate.php"
        <?php
        declare(strict_types = 1);

        use Formal\Migrations\{
            Factory,
            SQL\Migration,
        };
        use Formal\AccessLayer\Query;
        use Innmind\OperatingSystem\Factory as OS;
        use Innmind\Url\Url;
        use Innmind\Immutable\Sequence;

        require 'path/to/composer/autoload.php';

        $dsn = Url::of('mysql://user:pwd@127.0.0.1:3306/database');

        Factory::of(OS::build())
            ->storeVersionsInDatabase($dsn)
            ->sql()
            ->of(
                FeatureA\Migrations::load()
                    ->append(FeatureB\Migrations::load())
                    ->append(Etc\Migrations::load()),
            )
            ->migrate($dsn)
            ->match(
                static fn() => print('Everything has been migrated'),
                static fn(\Throwable $error) => printf(
                    'Migrations failed with the message : %s',
                    $error->getMessage(),
                ),
            );
        ```

    === "`FeatureA`"
        ```php
        <?php
        declare(strict_types = 1);

        namespace FeatureA;

        use Formal\Migrations\SQL\Migration;
        use Innmind\Immutable\Sequence;

        final class Migrations
        {
            /** @return Sequence<Migration> */
            public static function load(): Sequence
            {
                return Sequence::of(
                    Migration::of(
                        'init feature A',
                        SQL::of('CREATE TABLE `featureA` (`value` INT NOT NULL)'),
                    ),
                    // etc...
                );
            }
        }
        ```

    === "`FeatureB`"
        ```php
        <?php
        declare(strict_types = 1);

        namespace FeatureB;

        use Formal\Migrations\SQL\Migration;
        use Innmind\Immutable\Sequence;

        final class Migrations
        {
            /** @return Sequence<Migration> */
            public static function load(): Sequence
            {
                return Sequence::of(
                    Migration::of(
                        'init feature B',
                        SQL::of('CREATE TABLE `featureB` (`value` INT NOT NULL)'),
                    ),
                    // etc...
                );
            }
        }
        ```

    === "`Etc`"
        ```php
        <?php
        declare(strict_types = 1);

        namespace Etc;

        use Formal\Migrations\SQL\Migration;
        use Innmind\Immutable\Sequence;

        final class Migrations
        {
            /** @return Sequence<Migration> */
            public static function load(): Sequence
            {
                return Sequence::of(
                    Migration::of(
                        'init etc',
                        SQL::of('CREATE TABLE `etc` (`value` INT NOT NULL)'),
                    ),
                    // etc...
                );
            }
        }
        ```

    As long as each feature doesn't depend on each other it doesn't matter that the whole `Sequence` is not sorted. And even if they depend on each other you can sort them in `migrate.php` by calling `Sequence->sort()`.
