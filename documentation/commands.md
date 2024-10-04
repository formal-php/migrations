---
hide:
    - navigation
---

# Commands

This kind of migration is useful to run some commands automatically after deploying a new version of your app.

For example you have an [Elasticsearch](https://en.wikipedia.org/wiki/Elasticsearch) index and the new feature being deployed requires to rebuild the index just this one time. You can write a migration for this!

## Getting started

```php title="migrate.php"
<?php
declare(strict_types = 1);

use Formal\Migrations\{
    Factory,
    Commands\Migration,
};
use Innmind\OperatingSystem\Factory as OS;
use Innmind\Server\Control\Server\{
    Command,
    Process\TimedOut,
    Process\Failed,
    Process\Signaled,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

require 'path/to/composer/autoload.php';

Factory::of(OS::build())
    ->storeVersionsInDatabase(
        Url::of('mysql://user:pwd@127.0.0.1:3306/database'),
    )
    ->commands()
    ->of(Sequence::of(
        Migration::of(
            '2024-10-04 Rebuild Elasticsearch',
            Command::foreground('make elastic-destroy'), #(1)
            Command::foreground('make elastic-build'),
            Command::foreground('make elastic-index'),
        ),
    ))
    ->migrate()
    ->match(
        static fn() => print('Everything has been migrated'),
        static fn(TimedOut|Failed|Signaled $error) => printf(
            'Migrations failed with : %s',
            $error::class,
        ),
    );
```

1. Since this is a `Command` you can also specify an input `Content`, a working directory, and so on...

Then you can add `#!sh php migrate.php` in your deploy process.

??? tip
    Here the versions are stored in a database, but you may not have one depending on your project.

    Instead you can also store them on the filesystem by replacing `storeVersionsInDatabase()` by `storeVersionsOnFilesystem()`.

Like [SQL](sql.md) migrations your migrations name MUST be unique.

Here migrations will always be run in the order you specify them in the `Sequence`.

## Running a `Command` once

If you deploy 2 features that need to rebuild an Elasticsearch index you'll have 2 migrations doing the same thing. When deployed at the same time you'll do the same thing twice. This a waste of time!

You might be tempted to remove one of the migrations. But **DON'T**! Formal has a solution.

For this kind of situation you should use `Reference`s.

=== "Script"
    ```php title="migrate.php" hl_lines="28-30 34-36"
    <?php
    declare(strict_types = 1);

    use Formal\Migrations\{
        Factory,
        Commands\Migration,
    };
    use Innmind\OperatingSystem\Factory as OS;
    use Innmind\Server\Control\Server\{
        Command,
        Process\TimedOut,
        Process\Failed,
        Process\Signaled,
    };
    use Innmind\Url\Url;
    use Innmind\Immutable\Sequence;

    require 'path/to/composer/autoload.php';

    Factory::of(OS::build())
        ->storeVersionsInDatabase(
            Url::of('mysql://user:pwd@127.0.0.1:3306/database'),
        )
        ->commands()
        ->of(Sequence::of(
            Migration::of(
                '2024-10-04 Rebuild Elasticsearch for feature A',
                YourCommands::elasticDestroy,
                YourCommands::elasticBuild,
                YourCommands::elasticIndex,
            ),
            Migration::of(
                '2024-10-04 Rebuild Elasticsearch for feature B',
                YourCommands::elasticDestroy,
                YourCommands::elasticBuild,
                YourCommands::elasticIndex,
            ),
        ))
        ->migrate()
        ->match(
            static fn() => print('Everything has been migrated'),
            static fn(TimedOut|Failed|Signaled $error) => printf(
                'Migrations failed with : %s',
                $error::class,
            ),
        );
    ```

=== "`YourCommands`"
    ```php
    <?php
    declare(strict_types = 1);

    use Formal\Migrations\Commands\Reference;
    use Innmind\Server\Control\Server\Command;

    enum YourCommands implements Reference
    {
        case elasticDestroy;
        case elasticBuild;
        case elasticIndex;

        public function commands(): Command
        {
            return match ($this) {
                self::elasticDestroy => Command::foreground('make elastic-destroy'),
                self::elasticBuild => Command::foreground('make elastic-build'),
                self::elasticIndex => Command::foreground('make elastic-index'),
            };
        }
    }
    ```

If you:

- deploy feature A => the 3 commands are run
- deploy feature A then deploy feature B => the 3 commands are run twice
- deploy feature A and B at the same time => the 3 commands are run once

??? tip
    Since the `Command`s are defined in an enum you can't directly inject content (such as an input or a working directory). But you can still do it by calling the `migrate` method like this:

    ```php title="migrate.php"
    use Formal\Migrations\Commands\Reference;
    use Innmind\Server\Control\Server\Command;
    use Innmind\Filesystem\File\Content;

    //...

    ->migrate(
        null,
        static fn(Reference $reference) => static fn(Command $command) => match ($reference) {
            YourCommands::elasticDestroy => $command->withInput(Content::ofString('some input')),
            default => $command,
        },
    )
    //...
    ```

## Running migrations on a remote server

By default the commands are run the same machine the `migrate.php` script is run.

You can also run them on a remote server like this:

```php title="migrate.php" hl_lines="10 37-40"
<?php
declare(strict_types = 1);

use Formal\Migrations\{
    Factory,
    Commands\Migration,
};
use Innmind\OperatingSystem\{
    Factory as OS,
    OperatingSystem,
};
use Innmind\Server\Control\Server\{
    Command,
    Process\TimedOut,
    Process\Failed,
    Process\Signaled,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

require 'path/to/composer/autoload.php';

Factory::of(OS::build())
    ->storeVersionsInDatabase(
        Url::of('mysql://user:pwd@127.0.0.1:3306/database'),
    )
    ->commands()
    ->of(Sequence::of(
        Migration::of(
            '2024-10-04 Rebuild Elasticsearch',
            Command::foreground('make elastic-destroy'),
            Command::foreground('make elastic-build'),
            Command::foreground('make elastic-index'),
        ),
    ))
    ->migrate(
        static fn(OperatingSystem $os) => $os
            ->remote()
            ->ssh(Url::of('ssh://user@machine-name-or-ip:22/')) #(1)
            ->processes(),
    )
    ->match(
        static fn() => print('Everything has been migrated'),
        static fn(TimedOut|Failed|Signaled $error) => printf(
            'Migrations failed with : %s',
            $error::class,
        ),
    );
```

1. You should ssh keys to automatically log to the remote server.
