<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Formal\Migrations\{
    Migrations\All,
    Applied,
    Failure,
};
use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Either,
};

/**
 * @internal
 */
final class Runner
{
    private function __construct(
        private Manager $storage,
        private OperatingSystem $os,
        private Url $dsn,
    ) {
    }

    /**
     * @param All<Migration> $migrations
     *
     * @return Either<Failure, Applied>
     */
    public function __invoke(All $migrations): Either
    {
        return $this
            ->os
            ->remote()
            ->sql($this->dsn)
            ->either()
            ->leftMap(static fn($e) => Failure::of(
                $e,
                Sequence::of(),
            ))
            ->flatMap(fn($sql) => Applied::of(
                $this->os->clock(),
                $this->storage,
                $migrations
                    ->excludeAlreadyApplied($this->storage)
                    ->map(static fn($migration) => static fn() => $migration($sql)),
            ));
    }

    /**
     * @internal
     */
    public static function of(
        Manager $storage,
        OperatingSystem $os,
        Url $dsn,
    ): self {
        return new self($storage, $os, $dsn);
    }
}
