<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\Manager;
use Innmind\TimeContinuum\Clock;
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};
use Innmind\Immutable\{
    Sequence,
    Either,
    Maybe,
};

/**
 * @template C
 */
final readonly class Applied
{
    /**
     * @param Sequence<Version> $versions
     * @param Maybe<C> $error
     */
    private function __construct(
        private Clock $clock,
        private Manager $storage,
        private Sequence $versions,
        private Maybe $error,
    ) {
    }

    /**
     * @template T
     * @template E
     *
     * @param Sequence<Migration<T, E>> $migrations
     * @param T $kind
     *
     * @return self<E>
     */
    public static function of(
        Clock $clock,
        Manager $storage,
        Sequence $migrations,
        mixed $kind,
    ): self {
        $versions = $storage->repository(Version::class);

        return $migrations
            ->exclude(static fn($migration) => $versions->any(
                Property::of(
                    'name',
                    Sign::equality,
                    $migration->name(),
                ),
            ))
            ->reduce(
                self::new($clock, $storage),
                static fn(self $applied, $migration) => $applied->then(
                    $kind,
                    $migration,
                ),
            );
    }

    /**
     * @template R
     *
     * @param callable(Sequence<Version>): R $successfully
     * @param callable(C, Sequence<Version>): R $failed
     *
     * @return R
     */
    public function match(callable $successfully, callable $failed): mixed
    {
        /** @psalm-suppress MixedArgument */
        return $this->error->match(
            fn($error) => $failed($error, $this->versions),
            fn() => $successfully($this->versions),
        );
    }

    private static function new(Clock $clock, Manager $storage): self
    {
        return new self(
            $clock,
            $storage,
            Sequence::of(),
            Maybe::nothing(),
        );
    }

    /**
     * @template T
     *
     * @param T $kind
     * @param Migration<T, C> $migration
     *
     * @return self<C>
     */
    private function then(
        $kind,
        Migration $migration,
    ): self {
        return $this->error->match(
            fn() => $this,
            fn() => $migration($kind)
                ->map(fn() => Version::new(
                    $migration->name(),
                    $this->clock,
                ))
                ->match(
                    function($version) {
                        $this->storage->transactional(
                            function() use ($version) {
                                $this
                                    ->storage
                                    ->repository(Version::class)
                                    ->put($version);

                                return Either::right(null);
                            },
                        );

                        /** @var Maybe<C> */
                        $error = Maybe::nothing();

                        return new self(
                            $this->clock,
                            $this->storage,
                            ($this->versions)($version),
                            $error,
                        );
                    },
                    fn($error) => new self(
                        $this->clock,
                        $this->storage,
                        $this->versions,
                        Maybe::just($error),
                    ),
                ),
        );
    }
}
