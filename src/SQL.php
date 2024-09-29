<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\Manager;
use Formal\AccessLayer\Connection;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};
use Innmind\Immutable\{
    Sequence,
    Either,
};

final class SQL
{
    private function __construct(
        private Manager $storage,
        private OperatingSystem $os,
        private Url $dsn,
    ) {
    }

    /**
     * @param Sequence<Migration<Connection>> $migrations
     *
     * @return Sequence<Version>
     */
    public function __invoke(Sequence $migrations): Sequence
    {
        $versions = $this->storage->repository(Version::class);
        $sql = $this->os->remote()->sql($this->dsn);

        return $migrations
            ->exclude(static fn($migration) => $versions->any(
                Property::of(
                    'name',
                    Sign::equality,
                    $migration->name(),
                ),
            ))
            ->map(function($migration) use ($sql, $versions) {
                $migration($sql);

                $version = Version::new(
                    $migration->name(),
                    $this->os->clock(),
                );

                $this->storage->transactional(
                    static function() use ($version, $versions) {
                        $versions->put($version);

                        return Either::right(null);
                    },
                );

                return $version;
            });
    }

    public static function of(
        Manager $storage,
        OperatingSystem $os,
        Url $dsn,
    ): self {
        return new self($storage, $os, $dsn);
    }
}
