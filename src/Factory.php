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
use Innmind\TimeContinuum\PointInTime;
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

    public function storeVersionsInDatabase(Url $dsn): Factory\Configured
    {
        $connection = $this->os->remote()->sql($dsn);
        $aggregates = Aggregates::of(
            Types::of(
                Support::class(
                    PointInTime::class,
                    PointInTimeType::new($this->os->clock()),
                ),
            ),
        );

        return Factory\Configured::of(
            $this->os,
            Manager::sql($connection, $aggregates),
            static fn() => ShowCreateTable::of($aggregates)
                ->ifNotExists()(Version::class)
                ->foreach($connection),
        );
    }

    public function storeVersionsOnFilesystem(Path $location): Factory\Configured
    {
        return Factory\Configured::of(
            $this->os,
            Manager::filesystem(
                $this->os->filesystem()->mount($location),
                Aggregates::of(
                    Types::of(
                        Support::class(
                            PointInTime::class,
                            PointInTimeType::new($this->os->clock()),
                        ),
                    ),
                ),
            ),
        );
    }
}
