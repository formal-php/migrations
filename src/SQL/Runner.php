<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Formal\Migrations\{
    Migration,
    Applied,
};
use Formal\ORM\Manager;
use Formal\AccessLayer\Connection;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

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
     * @param Sequence<Migration<Connection>> $migrations
     */
    public function __invoke(Sequence $migrations): Applied
    {
        $sql = $this->os->remote()->sql($this->dsn)->unwrap();

        return Applied::of(
            $this->os->clock(),
            $this->storage,
            $migrations,
            $sql,
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
