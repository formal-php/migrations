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
use Innmind\Immutable\Sequence;

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
     */
    public function __invoke(Sequence $migrations): Applied
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
            ->reduce(
                Applied::new($this->os->clock(), $this->storage),
                static fn(Applied $applied, $migration) => $applied->then(
                    $sql,
                    $migration,
                ),
            );
    }

    public static function of(
        Manager $storage,
        OperatingSystem $os,
        Url $dsn,
    ): self {
        return new self($storage, $os, $dsn);
    }
}
