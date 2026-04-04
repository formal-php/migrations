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
     */
    public function storeVersionsInDatabase(
        Url $dsn,
        ?string $table = null,
    ): Factory\Configured {
        $connection = $this->os->remote()->sql($dsn)->unwrap();
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

        return Factory\Configured::of(
            $this->os,
            Manager::sql($connection, $aggregates),
            static fn() => ShowCreateTable::of($aggregates)
                ->ifNotExists()(Version::class)
                ->foreach(static fn($query) => $connection($query)),
        );
    }

    public function storeVersionsOnFilesystem(Path $location): Factory\Configured
    {
        return Factory\Configured::of(
            $this->os,
            Manager::filesystem(
                $this->os->filesystem()->mount($location)->unwrap(),
                Aggregates::of(
                    Types::of(
                        Support::class(
                            Point::class,
                            PointInTimeType::new($this->os->clock()),
                        ),
                    ),
                ),
            ),
        );
    }
}
