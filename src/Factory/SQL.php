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
    Attempt,
    SideEffect,
};

final readonly class SQL
{
    /**
     * @param \Closure(): Attempt<SideEffect> $setup
     * @param Attempt<All<Migration>> $migrations
     */
    private function __construct(
        private OperatingSystem $os,
        private Manager $storage,
        private \Closure $setup,
        private Attempt $migrations,
    ) {
    }

    /**
     * @internal
     *
     * @param \Closure(): Attempt<SideEffect> $setup
     */
    public static function new(
        OperatingSystem $os,
        Manager $storage,
        \Closure $setup,
    ): self {
        return new self(
            $os,
            $storage,
            $setup,
            Attempt::result(All::none(Migration::class)),
        );
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
            Attempt::result(All::of($migrations)),
        );
    }

    public function files(Path $location): self
    {
        return new self(
            $this->os,
            $this->storage,
            $this->setup,
            $this
                ->os
                ->filesystem()
                ->mount($location)
                ->map(Load::files(...))
                ->map(All::of(...)),
        );
    }

    /**
     * @return Either<Failure, Applied>
     */
    public function migrate(Url $dsn): Either
    {
        return ($this->setup)()
            ->flatMap(fn() => $this->migrations)
            ->either()
            ->leftMap(static fn($e) => Failure::of(
                $e,
                Sequence::of(),
            ))
            ->flatMap(
                fn($migrations) => Runner::of($this->storage, $this->os, $dsn)($migrations),
            );
    }
}
