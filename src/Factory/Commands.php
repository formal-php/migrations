<?php
declare(strict_types = 1);

namespace Formal\Migrations\Factory;

use Formal\Migrations\{
    Commands\Runner,
    Commands\Reference,
    Commands\Migration,
    Migrations\All,
    Failure,
    Applied,
};
use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\Immutable\{
    Sequence,
    Either,
    Attempt,
    SideEffect,
};

final readonly class Commands
{
    /**
     * @param \Closure(): Attempt<SideEffect> $setup
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
     * @param \Closure(): Attempt<SideEffect> $setup
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

    /**
     * @param ?callable(OperatingSystem): Processes $build
     * @param ?callable(Reference): (callable(Command): Command) $configure
     *
     * @return Either<Failure, Applied>
     */
    public function migrate(
        ?callable $build = null,
        ?callable $configure = null,
    ): Either {
        return ($this->setup)()
            ->either()
            ->leftMap(static fn($e) => Failure::of(
                $e,
                Sequence::of(),
            ))
            ->flatMap(fn() => Runner::of(
                $this->storage,
                $this->os,
                $build,
                $configure,
            )($this->migrations));
    }
}
