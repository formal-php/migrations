<?php
declare(strict_types = 1);

use Fixtures\Formal\Migrations\Ref;
use Formal\Migrations\{
    Commands,
    Commands\Migration,
    Version,
};
use Formal\ORM\{
    Manager,
    Definition\Aggregates,
    Definition\Types,
    Definition\Type\Support,
    Definition\Type\PointInTimeType,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\Adapter\InMemory;
use Innmind\Server\Control\Server\Command;
use Innmind\TimeContinuum\PointInTime;
use Innmind\Url\Path;
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Commands migrations',
        given(
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->atLeast(1),
            ),
        ),
        static function($assert, $names) {
            [$a, $b, $c, $d] = $names;
            $tmp = \sys_get_temp_dir().'/formal/migrations';
            @\mkdir($tmp, recursive: true);

            $os = Factory::build();

            $migrations = Commands::of(
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
                null,
                static fn() => static fn($command) => $command->withWorkingDirectory(Path::of($tmp)),
            );

            $versions = $migrations(Sequence::of(
                Migration::of(
                    $a,
                    Command::foreground('touch test')
                        ->withWorkingDirectory(Path::of($tmp)),
                ),
                Migration::of(
                    $b,
                    Ref::rm,
                ),
                Migration::of(
                    $c,
                    Command::foreground('echo foo >> test')
                        ->withWorkingDirectory(Path::of($tmp)),
                ),
                Migration::of(
                    $d,
                    Ref::rm,
                ),
            ));

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

            $assert->true(\file_exists($tmp.'/test'));
            $assert->same(
                "foo\n",
                \file_get_contents($tmp.'/test'),
            );
        },
    );
};
