<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Filesystem\File;
use Innmind\Immutable\{
    Sequence,
    Attempt,
    Predicate\Instance,
};

final class Migration
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

    /**
     * @internal
     *
     * @return Attempt<non-empty-string>
     */
    public function __invoke(Connection $connection): Attempt
    {
        return Attempt::of(fn() => $this->queries->foreach(
            static fn($query) => $connection($query),
        ))->map(fn() => $this->name);
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

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }
}
