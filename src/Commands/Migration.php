<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Formal\Migrations\Migration as MigrationInterface;
use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    SideEffect,
};

/**
 * @implements MigrationInterface<Run>
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

    public function __invoke($kind): Maybe
    {
        return $this->commands->reduce(
            Maybe::just(new SideEffect),
            static fn(Maybe $state, $command) => $state->flatMap(
                static fn() => $kind($command)
                    ->maybe()
                    ->map(static fn() => new SideEffect),
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
