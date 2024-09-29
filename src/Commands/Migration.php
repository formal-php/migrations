<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Formal\Migrations\Migration as MigrationInterface;
use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\Sequence;

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

    public function __invoke($kind): void
    {
        $_ = $this->commands->foreach(
            static fn($command) => $kind($command)->match(
                static fn() => null,
                static fn($error) => throw new \RuntimeException($error::class),
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
