<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\{
    Sequence,
    Attempt,
    SideEffect,
};

final class Migration
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

    /**
     * @internal
     *
     * @return Attempt<non-empty-string>
     */
    public function __invoke(Run $run): Attempt
    {
        return $this
            ->commands
            ->sink(SideEffect::identity)
            ->attempt(static fn($sideEffect, $command) => $run($command))
            ->map(fn() => $this->name);
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

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }
}
