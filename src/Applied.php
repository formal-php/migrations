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
};

final readonly class Applied
{
    /**
     * @param Sequence<Version> $versions
     */
    private function __construct(
        private Clock $clock,
        private Manager $storage,
        private Sequence $versions,
        private bool $successfully,
    ) {
    }

    /**
     * @template T
     *
     * @param Sequence<Migration<T>> $migrations
     * @param T $kind
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
     * @param callable(Sequence<Version>): R $failed
     *
     * @return R
     */
    public function match(callable $successfully, callable $failed): mixed
    {
        return match ($this->successfully) {
            true => $successfully($this->versions),
            false => $failed($this->versions),
        };
    }

    private static function new(Clock $clock, Manager $storage): self
    {
        return new self(
            $clock,
            $storage,
            Sequence::of(),
            true,
        );
    }

    /**
     * @template T
     *
     * @param T $kind
     * @param Migration<T> $migration
     */
    private function then(
        $kind,
        Migration $migration,
    ): self {
        if (!$this->successfully) {
            return $this;
        }

        return $migration($kind)
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

                    return new self(
                        $this->clock,
                        $this->storage,
                        ($this->versions)($version),
                        true,
                    );
                },
                fn() => new self(
                    $this->clock,
                    $this->storage,
                    $this->versions,
                    false,
                ),
            );
    }
}
