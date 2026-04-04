<?php
declare(strict_types = 1);

namespace Formal\Migrations\Migrations;

use Formal\Migrations\{
    Commands,
    SQL,
    Version,
};
use Formal\ORM\Manager;
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @template T of Commands\Migration|SQL\Migration
 */
final class All
{
    /**
     * @param Sequence<T> $migrations
     */
    private function __construct(
        private Sequence $migrations,
    ) {
    }

    /**
     * @internal
     * @template A of Commands\Migration|SQL\Migration
     *
     * @param Sequence<A> $migrations
     *
     * @return self<A>
     */
    public static function of(Sequence $migrations): self
    {
        return new self($migrations);
    }

    /**
     * @internal
     * @template C of Commands\Migration
     * @template S of SQL\Migration
     *
     * @param class-string<C>|class-string<S> $type
     *
     * @return self<C|S>
     */
    public static function none(string $type): self
    {
        /** @var self<C|S> */
        return new self(Sequence::of());
    }

    /**
     * @return Sequence<T>
     */
    public function excludeAlreadyApplied(Manager $storage): Sequence
    {
        $versions = $storage->repository(Version::class);

        return $this
            ->migrations
            ->exclude(static fn($migration) => $versions->any(
                Property::of(
                    'name',
                    Sign::equality,
                    $migration->name(),
                ),
            ));
    }
}
