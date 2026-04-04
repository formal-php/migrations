<?php
declare(strict_types = 1);

namespace Formal\Migrations\Factory;

use Formal\Migrations\{
    SQL\Runner,
    SQL\Load,
    SQL\Migration,
    Migrations\All,
    Failure,
    Applied,
};
use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\{
    Sequence,
    Either,
};

final readonly class SQL
{
    /**
     * @param \Closure(): void $setup
     * @param All<Migration> $migrations
     */
    private function __construct(
        private OperatingSystem $os,
        private Manager $storage,
        private \Closure $setup,
        private All $migrations,
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
        return new self($os, $storage, $setup, All::none(Migration::class));
    }

    /**
     * @param Sequence<Migration> $migrations
     */
    public function of(Sequence $migrations): self
    {
        return new self(
            $this->os,
            $this->storage,
            $this->setup,
            All::of($migrations),
        );
    }

    public function files(Path $location): self
    {
        return new self(
            $this->os,
            $this->storage,
            $this->setup,
            All::of(Load::files($this->os->filesystem()->mount($location)->unwrap())),
        );
    }

    /**
     * @return Either<Failure, Applied>
     */
    public function migrate(Url $dsn): Either
    {
        ($this->setup)();

        return Runner::of($this->storage, $this->os, $dsn)($this->migrations);
    }
}
