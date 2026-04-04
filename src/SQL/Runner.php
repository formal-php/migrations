<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Formal\Migrations\{
    Applied,
    Migrations\All,
};
use Formal\ORM\Manager;
use Formal\AccessLayer\Connection;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;

/**
 * @internal
 */
final class Runner
{
    private function __construct(
        private Manager $storage,
        private OperatingSystem $os,
        private Url $dsn,
    ) {
    }

    /**
     * @param All<Connection> $migrations
     */
    public function __invoke(All $migrations): Applied
    {
        $sql = $this->os->remote()->sql($this->dsn)->unwrap();

        return Applied::of(
            $this->os->clock(),
            $this->storage,
            $migrations
                ->excludeAlreadyApplied($this->storage)
                ->map(static fn($migration) => static fn() => $migration($sql)->map(
                    static fn() => $migration->name(),
                )),
        );
    }

    /**
     * @internal
     */
    public static function of(
        Manager $storage,
        OperatingSystem $os,
        Url $dsn,
    ): self {
        return new self($storage, $os, $dsn);
    }
}
