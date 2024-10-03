<?php
declare(strict_types = 1);

namespace Formal\Migrations\Factory;

use Formal\Migrations\{
    SQL as Runner,
    Applied,
    Migration,
};
use Formal\ORM\Manager;
use Formal\AccessLayer\Connection;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\Sequence;

final readonly class SQL
{
    /**
     * @param \Closure(): void $setup
     * @param Sequence<Migration<Connection, \Throwable>> $migrations
     */
    private function __construct(
        private OperatingSystem $os,
        private Manager $storage,
        private \Closure $setup,
        private Sequence $migrations,
    ) {
    }

    /**
     * @internal
     *
     * @param \Closure(): void $setup
     */
    public static function new(
        OperatingSystem $os,
        Manager $storage,
        \Closure $setup,
    ): self {
        return new self($os, $storage, $setup, Sequence::of());
    }

    /**
     * @param Sequence<Migration<Connection, \Throwable>> $migrations
     */
    public function of(Sequence $migrations): self
    {
        return new self(
            $this->os,
            $this->storage,
            $this->setup,
            $migrations,
        );
    }

    public function files(Path $location): self
    {
        return new self(
            $this->os,
            $this->storage,
            $this->setup,
            Runner\Load::files($this->os->filesystem()->mount($location)),
        );
    }

    public function migrate(Url $dsn): Applied
    {
        ($this->setup)();

        return Runner::of($this->storage, $this->os, $dsn)($this->migrations);
    }
}
