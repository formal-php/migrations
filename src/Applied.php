<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\Manager;
use Innmind\TimeContinuum\Clock;
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

    public static function new(Clock $clock, Manager $storage): self
    {
        return new self(
            $clock,
            $storage,
            Sequence::of(),
            true,
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

    /**
     * @template T
     *
     * @param T $kind
     * @param Migration<T> $migration
     */
    public function then(
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
