<?php
declare(strict_types = 1);

namespace Formal\Migrations\Migrations;

use Formal\Migrations\{
    Migration,
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
 * @template T
 */
final class All
{
    /**
     * @param Sequence<Migration<T>> $migrations
     */
    private function __construct(
        private Sequence $migrations,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param Sequence<Migration<A>> $migrations
     *
     * @return self<A>
     */
    public static function of(Sequence $migrations): self
    {
        return new self($migrations);
    }

    /**
     * @internal
     * @template C of object
     *
     * @param class-string<C> $type
     *
     * @return self<C>
     */
    public static function none(string $type): self
    {
        /** @var self<C> */
        return new self(Sequence::of());
    }

    /**
     * @return Sequence<Migration<T>>
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
