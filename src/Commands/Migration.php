<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Formal\Migrations\Migration as MigrationInterface;
use Innmind\Server\Control\Server\{
    Command,
    Process\TimedOut,
    Process\Failed,
    Process\Signaled,
};
use Innmind\Immutable\{
    Sequence,
    Either,
    SideEffect,
};

/**
 * @implements MigrationInterface<Run, TimedOut|Failed|Signaled>
 */
final class Migration implements MigrationInterface
{
    /**
     * @param non-empty-string $name
     * @param Sequence<Command|Reference> $commands
     */
    private function __construct(
        private string $name,
        private Sequence $commands,
    ) {
    }

    public function __invoke($kind): Either
    {
        /** @var Either<TimedOut|Failed|Signaled, SideEffect> */
        return $this->commands->reduce(
            Either::right(new SideEffect),
            static fn(Either $state, $command) => $state->flatMap(
                static fn() => $kind($command)->map(
                    static fn() => new SideEffect,
                ),
            ),
        );
    }

    /**
     * @no-named-arguments
     *
     * @param non-empty-string $name
     */
    public static function of(
        string $name,
        Command|Reference ...$commands,
    ): self {
        return new self($name, Sequence::of(...$commands));
    }

    public function name(): string
    {
        return $this->name;
    }
}
