<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Formal\Migrations\Migration as MigrationInterface;
use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Filesystem\File;
use Innmind\Immutable\{
    Sequence,
    Either,
    Predicate\Instance,
};

/**
 * @implements MigrationInterface<Connection, \Throwable>
 */
final class Migration implements MigrationInterface
{
    /**
     * @param non-empty-string $name
     * @param Sequence<Query> $queries
     */
    private function __construct(
        private string $name,
        private Sequence $queries,
    ) {
    }

    public function __invoke($kind): Either
    {
        try {
            return Either::right($this->queries->foreach($kind));
        } catch (\Throwable $e) {
            return Either::left($e);
        }
    }

    /**
     * @no-named-arguments
     *
     * @param non-empty-string $name
     */
    public static function of(
        string $name,
        Query ...$queries,
    ): self {
        return new self($name, Sequence::of(...$queries));
    }

    public static function file(File $file): self
    {
        return new self(
            $file->name()->toString(),
            $file
                ->content()
                ->lines()
                ->map(static fn($line) => $line->str())
                ->map(Line::parse(...))
                ->aggregate(Line::window(...))
                ->map(static fn($line) => $line->query())
                ->keep(Instance::of(Query::class)),
        );
    }

    public function name(): string
    {
        return $this->name;
    }
}
