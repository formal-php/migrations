<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\{
    Manager,
    Definition\Aggregates,
    Definition\Types,
    Definition\Type\Support,
    Definition\Type\PointInTimeType,
    Adapter\SQL\ShowCreateTable,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Time\Point;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\Attempt;

final class Factory
{
    private function __construct(
        private OperatingSystem $os,
    ) {
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }

    /**
     * @param ?non-empty-string $table
     *
     * @return Attempt<Factory\Configured>
     */
    public function storeVersionsInDatabase(
        Url $dsn,
        ?string $table = null,
    ): Attempt {
        $aggregates = Aggregates::of(
            Types::of(
                Support::class(
                    Point::class,
                    PointInTimeType::new($this->os->clock()),
                ),
            ),
        );

        if (\is_string($table)) {
            $aggregates = $aggregates->mapName(static fn() => $table);
        }

        return $this
            ->os
            ->remote()
            ->sql($dsn)
            ->map(fn($connection) => Factory\Configured::of(
                $this->os,
                Manager::sql($connection, $aggregates),
                static fn() => Attempt::of(
                    static fn() => ShowCreateTable::of($aggregates)
                        ->ifNotExists()(Version::class)
                        ->foreach(static fn($query) => $connection($query)),
                ),
            ));
    }

    /**
     * @return Attempt<Factory\Configured>
     */
    public function storeVersionsOnFilesystem(Path $location): Attempt
    {
        return $this
            ->os
            ->filesystem()
            ->mount($location)
            ->map(fn($storage) => Factory\Configured::of(
                $this->os,
                Manager::filesystem(
                    $storage,
                    Aggregates::of(
                        Types::of(
                            Support::class(
                                Point::class,
                                PointInTimeType::new($this->os->clock()),
                            ),
                        ),
                    ),
                ),
            ));
    }
}
