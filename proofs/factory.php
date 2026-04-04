<?php
declare(strict_types = 1);

use Formal\Migrations\{
    Factory,
    SQL,
    Commands,
};
use Formal\AccessLayer\Query;
use Innmind\OperatingSystem\Factory as OS;
use Innmind\Filesystem\File;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Factory',
        static function($assert) {
            $os = OS::build();

            $port = \getenv('DB_PORT') ?: '3306';
            $dsn = Url::of("mysql://root:root@127.0.0.1:$port/example");
            $sql = \sys_get_temp_dir().'/formal/migrations/sql/';
            @\mkdir($sql, recursive: true);
            $tmp = \sys_get_temp_dir().'/formal/migrations/tmp/';
            @\mkdir($tmp, recursive: true);
            $fs = $os->filesystem()->mount(Path::of($tmp))->unwrap();
            $_ = $fs
                ->root()
                ->all()
                ->map(static fn($file) => $file->name())
                ->foreach(static fn($file) => $fs->remove($file)->unwrap());

            $_ = $os
                ->filesystem()
                ->mount(Path::of($sql))
                ->unwrap()
                ->add(File::named(
                    'a.sql',
                    File\Content::ofString(<<<SQL
                    create table if not exists `test` (`value` int not null)
                    --
                    drop table `test`
                    SQL),
                ))
                ->unwrap();
            $_ = $os
                ->remote()
                ->sql($dsn)
                ->unwrap()(Query::of('drop table if exists `version`'));

            [$successfully, $versions] = Factory::of($os)
                ->storeVersionsInDatabase($dsn)
                ->sql()
                ->of(Sequence::of(
                    SQL\Migration::of(
                        'a',
                        Query::of('create table if not exists `test` (`value` int not null)'),
                        Query::of('drop table `test`'),
                    ),
                ))
                ->migrate($dsn)
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($_, $versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->same(1, $versions->size());

            [$successfully, $versions] = Factory::of($os)
                ->storeVersionsInDatabase($dsn)
                ->sql()
                ->files(Path::of($sql))
                ->migrate($dsn)
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($_, $versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->same(1, $versions->size());

            [$successfully, $versions] = Factory::of($os)
                ->storeVersionsOnFilesystem(Path::of($tmp))
                ->commands()
                ->of(Sequence::of(
                    Commands\Migration::of('echo test'),
                ))
                ->migrate()
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->same(1, $versions->size());
        },
    );

    yield proof(
        'Store migrations versions in a specified table name',
        given(
            Set::strings()
                ->madeOf(
                    Set::strings()->chars()->uppercaseLetter(),
                    Set::strings()->chars()->lowercaseLetter(),
                )
                ->between(1, 64),
        ),
        static function($assert, $table) {
            $os = OS::build();

            $port = \getenv('DB_PORT') ?: '3306';
            $dsn = Url::of("mysql://root:root@127.0.0.1:$port/example");
            $sql = $os->remote()->sql($dsn)->unwrap();

            $_ = $sql(Query::of("drop table if exists `$table`"));

            $migrations = Factory::of($os)
                ->storeVersionsInDatabase($dsn, $table)
                ->sql()
                ->of(Sequence::of(
                    SQL\Migration::of(
                        'a',
                        Query::of('create table if not exists `test` (`value` int not null)'),
                        Query::of('drop table `test`'),
                    ),
                ));

            [$successfully, $versions] = $migrations
                ->migrate($dsn)
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($_, $versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->same(1, $versions->size());

            [$successfully, $versions] = $migrations
                ->migrate($dsn)
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($_, $versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->same(0, $versions->size());

            $assert->same(
                1,
                $sql(Query::of("select * from `$table`"))->size(),
            );
        },
    );
};
