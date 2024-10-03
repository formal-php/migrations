<?php
declare(strict_types = 1);

use Formal\Migrations\{
    SQL,
    SQL\Migration,
    SQL\Load,
    Version,
};
use Formal\ORM\{
    Manager,
    Definition\Aggregates,
    Definition\Types,
    Definition\Type\Support,
    Definition\Type\PointInTimeType,
};
use Formal\AccessLayer\Query;
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\PointInTime;
use Innmind\Filesystem\{
    Adapter\InMemory,
    File,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Either,
};
use Innmind\BlackBox\Set;

return static function() {
    $os = Factory::build();

    $port = \getenv('DB_PORT') ?: '3306';
    $dsn = Url::of("mysql://root:root@127.0.0.1:$port/example");

    yield proof(
        'SQL migrations',
        given(
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
            ),
        ),
        static function($assert, $names) use ($os, $dsn) {
            [$a, $b, $c, $d] = $names;

            // setup
            $os
                ->remote()
                ->sql($dsn)(
                    Query\SQL::of('drop table if exists `test`'),
                );

            $migrations = SQL::of(
                $storage = Manager::filesystem(
                    InMemory::emulateFilesystem(),
                    Aggregates::of(
                        Types::of(
                            Support::class(
                                PointInTime::class,
                                PointInTimeType::new($os->clock()),
                            ),
                        ),
                    ),
                ),
                $os,
                $dsn,
            );

            [$successfully, $versions] = $migrations(Sequence::of(
                Migration::of(
                    $a,
                    Query\SQL::of('create table `test` (`value` int not null)'),
                ),
                Migration::of(
                    $b,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('insert into `test` values (1)'),
                    Query\SQL::of('commit'),
                ),
                Migration::of(
                    $c,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('insert into `test` values (3)'),
                    Query\SQL::of('commit'),
                ),
                Migration::of(
                    $d,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('delete from `test` where `value` > 2'),
                    Query\SQL::of('commit'),
                ),
            ))->match(
                static fn($versions) => [true, $versions],
                static fn($versions) => [false, $versions],
            );

            $assert->true($successfully);
            $assert->count(4, $versions);
            $assert->same(
                [$a, $b, $c, $d],
                $versions
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $assert->same(
                [$a, $b, $c, $d],
                $versions
                    ->sort(static fn($a, $b) => $a->appliedAt()->aheadOf($b->appliedAt()) ? 1 : -1)
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $stored = $storage
                ->repository(Version::class)
                ->all()
                ->map(static fn($version) => $version->name())
                ->toList();
            $assert
                ->expected($a)
                ->in($stored);
            $assert
                ->expected($b)
                ->in($stored);
            $assert
                ->expected($c)
                ->in($stored);
            $assert
                ->expected($d)
                ->in($stored);

            $assert->same(
                [['value' => 1]],
                $os
                    ->remote()
                    ->sql($dsn)(
                        Query\SQL::of('select * from `test`'),
                    )
                    ->map(static fn($row) => $row->toArray())
                    ->toList(),
            );
        },
    );

    yield proof(
        'SQL partial migrations',
        given(
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
            ),
        ),
        static function($assert, $names) use ($os, $dsn) {
            [$a, $b, $c, $d] = $names;

            // setup
            $os
                ->remote()
                ->sql($dsn)(
                    Query\SQL::of('drop table if exists `test`'),
                );

            $migrations = SQL::of(
                $storage = Manager::filesystem(
                    InMemory::emulateFilesystem(),
                    Aggregates::of(
                        Types::of(
                            Support::class(
                                PointInTime::class,
                                PointInTimeType::new($os->clock()),
                            ),
                        ),
                    ),
                ),
                $os,
                $dsn,
            );

            $storage->transactional(
                static function() use ($storage, $os, $d) {
                    $storage
                        ->repository(Version::class)
                        ->put(Version::new($d, $os->clock()));

                    return Either::right(null);
                },
            );

            [$successfully, $versions] = $migrations(Sequence::of(
                Migration::of(
                    $a,
                    Query\SQL::of('create table `test` (`value` int not null)'),
                ),
                Migration::of(
                    $b,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('insert into `test` values (1)'),
                    Query\SQL::of('commit'),
                ),
                Migration::of(
                    $c,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('insert into `test` values (3)'),
                    Query\SQL::of('commit'),
                ),
                Migration::of(
                    $d,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('delete from `test` where `value` > 2'),
                    Query\SQL::of('commit'),
                ),
            ))->match(
                static fn($versions) => [true, $versions],
                static fn($versions) => [false, $versions],
            );

            $assert->true($successfully);
            $assert->count(3, $versions);
            $assert->same(
                [$a, $b, $c],
                $versions
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $assert->same(
                [$a, $b, $c],
                $versions
                    ->sort(static fn($a, $b) => $a->appliedAt()->aheadOf($b->appliedAt()) ? 1 : -1)
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $stored = $storage
                ->repository(Version::class)
                ->all()
                ->map(static fn($version) => $version->name())
                ->toList();
            $assert
                ->expected($a)
                ->in($stored);
            $assert
                ->expected($b)
                ->in($stored);
            $assert
                ->expected($c)
                ->in($stored);
            $assert
                ->expected($d)
                ->in($stored);

            $assert->same(
                [['value' => 1], ['value' => 3]],
                $os
                    ->remote()
                    ->sql($dsn)(
                        Query\SQL::of('select * from `test`'),
                    )
                    ->map(static fn($row) => $row->toArray())
                    ->toList(),
            );
        },
    );

    yield proof(
        'SQL migrations from raw files',
        given(
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
            ),
        ),
        static function($assert, $names) use ($os, $dsn) {
            \sort($names);
            [$a, $b, $c, $d] = $names;

            // setup
            $os
                ->remote()
                ->sql($dsn)(
                    Query\SQL::of('drop table if exists `test`'),
                );
            $filesystem = InMemory::emulateFilesystem();
            $sql = [
                ['create table `test` (`value` int not null)'],
                [
                    'start transaction',
                    '--',
                    'insert into `test` values (1)',
                    '--',
                    'commit',
                ],
                [ // no comment lines to show that it's run as a single query
                    'start transaction;',
                    'insert into `test` values (3);',
                    'commit;',
                ],
                [
                    'start transaction',
                    '--',
                    'delete from `test` where `value` > 2',
                    '--',
                    'commit',
                ],
            ];

            foreach ($names as $i => $name) {
                $filesystem->add(File::named(
                    $name,
                    File\Content::ofString(\implode("\n", $sql[$i])),
                ));
            }

            $migrations = SQL::of(
                $storage = Manager::filesystem(
                    InMemory::emulateFilesystem(),
                    Aggregates::of(
                        Types::of(
                            Support::class(
                                PointInTime::class,
                                PointInTimeType::new($os->clock()),
                            ),
                        ),
                    ),
                ),
                $os,
                $dsn,
            );

            [$successfully, $versions] = $migrations(Load::files($filesystem))->match(
                static fn($versions) => [true, $versions],
                static fn($versions) => [false, $versions],
            );

            $assert->true($successfully);
            $assert->count(4, $versions);
            $assert->same(
                [$a, $b, $c, $d],
                $versions
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $assert->same(
                [$a, $b, $c, $d],
                $versions
                    ->sort(static fn($a, $b) => $a->appliedAt()->aheadOf($b->appliedAt()) ? 1 : -1)
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $stored = $storage
                ->repository(Version::class)
                ->all()
                ->map(static fn($version) => $version->name())
                ->toList();
            $assert
                ->expected($a)
                ->in($stored);
            $assert
                ->expected($b)
                ->in($stored);
            $assert
                ->expected($c)
                ->in($stored);
            $assert
                ->expected($d)
                ->in($stored);

            $assert->same(
                [['value' => 1]],
                $os
                    ->remote()
                    ->sql($dsn)(
                        Query\SQL::of('select * from `test`'),
                    )
                    ->map(static fn($row) => $row->toArray())
                    ->toList(),
            );
        },
    );

    yield proof(
        'SQL failing migrations',
        given(
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
            ),
        ),
        static function($assert, $names) use ($os, $dsn) {
            [$a, $b, $c] = $names;

            // setup
            $os
                ->remote()
                ->sql($dsn)(
                    Query\SQL::of('drop table if exists `test`'),
                );

            $migrations = SQL::of(
                $storage = Manager::filesystem(
                    InMemory::emulateFilesystem(),
                    Aggregates::of(
                        Types::of(
                            Support::class(
                                PointInTime::class,
                                PointInTimeType::new($os->clock()),
                            ),
                        ),
                    ),
                ),
                $os,
                $dsn,
            );

            [$successfully, $versions, $error] = $migrations(Sequence::of(
                Migration::of(
                    $a,
                    Query\SQL::of('create table `test` (`value` int not null)'),
                ),
                Migration::of(
                    $b,
                    Query\SQL::of('create table `test` (`value` int not null)'),
                ),
                Migration::of(
                    $c,
                    Query\SQL::of('start transaction'),
                    Query\SQL::of('insert into `test` values (3)'),
                    Query\SQL::of('commit'),
                ),
            ))->match(
                static fn($versions) => [true, $versions, null],
                static fn($error, $versions) => [false, $versions, $error],
            );

            $assert->false($successfully);
            $assert
                ->object($error)
                ->instance(Throwable::class);
            $assert->same(
                "Query 'create table `test` (`value` int not null)' failed with: [42S01] [1050] Table 'test' already exists",
                $error->getMessage(),
            );
            $assert->count(1, $versions);
            $assert->same(
                [$a],
                $versions
                    ->map(static fn($version) => $version->name())
                    ->toList(),
            );
            $stored = $storage
                ->repository(Version::class)
                ->all()
                ->map(static fn($version) => $version->name())
                ->toList();
            $assert
                ->expected($a)
                ->in($stored);
            $assert
                ->expected($b)
                ->not()
                ->in($stored);
            $assert
                ->expected($c)
                ->not()
                ->in($stored);

            $assert->same(
                [],
                $os
                    ->remote()
                    ->sql($dsn)(
                        Query\SQL::of('select * from `test`'),
                    )
                    ->map(static fn($row) => $row->toArray())
                    ->toList(),
            );
        },
    );
};
