<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Formal\Migrations\Migration as MigrationInterface;
use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\{
    Sequence,
    Either,
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

    #[\Override]
    public function __invoke($kind): Either
    {
        return $this
            ->commands
            ->sink(SideEffect::identity)
            ->either(static fn($sideEffect, $command) => $kind($command)->map(
                static fn() => $sideEffect,
            ));
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

    #[\Override]
    public function name(): string
    {
        return $this->name;
    }
}
