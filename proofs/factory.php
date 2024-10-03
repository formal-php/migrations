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
            $fs = $os->filesystem()->mount(Path::of($tmp));
            $fs
                ->root()
                ->all()
                ->map(static fn($file) => $file->name())
                ->foreach($fs->remove(...));

            $os
                ->filesystem()
                ->mount(Path::of($sql))
                ->add(File::named(
                    'a.sql',
                    File\Content::ofString(<<<SQL
                    create table if not exists `test` (`value` int not null)
                    --
                    drop table `test`
                    SQL),
                ));
            $os
                ->remote()
                ->sql($dsn)(Query\SQL::of('drop table if exists `version`'));

            [$successfully, $versions] = Factory::of($os)
                ->storeVersionsInDatabase($dsn)
                ->sql()
                ->of(Sequence::of(
                    SQL\Migration::of(
                        'a',
                        Query\SQL::of('create table if not exists `test` (`value` int not null)'),
                        Query\SQL::of('drop table `test`'),
                    ),
                ))
                ->migrate($dsn)
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->count(1, $versions);

            [$successfully, $versions] = Factory::of($os)
                ->storeVersionsInDatabase($dsn)
                ->sql()
                ->files(Path::of($sql))
                ->migrate($dsn)
                ->match(
                    static fn($versions) => [true, $versions],
                    static fn($versions) => [false, $versions],
                );

            $assert->true($successfully);
            $assert->count(1, $versions);

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
            $assert->count(1, $versions);
        },
    );
};
