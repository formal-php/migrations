<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\{
    Manager,
    Adapter\Transaction,
};
use Innmind\Time\Clock;
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
     * @param Either<array{\Throwable, Sequence<Version>}, Sequence<Version>> $result
     */
    private function __construct(
        private Either $result,
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
        /** @var Sequence<Version> */
        $applied = Sequence::of();
        /** @var Either<array{\Throwable, Sequence<Version>}, Sequence<Version>> */
        $result = Either::right($applied);

        $result = $migrations
            ->exclude(static fn($migration) => $versions->any(
                Property::of(
                    'name',
                    Sign::equality,
                    $migration->name(),
                ),
            ))
            ->sink($applied)
            ->either(
                static fn($applied, $migration) => $migration($kind)
                    ->map(static fn() => Version::new(
                        $migration->name(),
                        $clock,
                    ))
                    ->either()
                    ->eitherWay(
                        static fn($version) => $storage
                            ->transactional(
                                static fn() => $versions
                                    ->put($version)
                                    ->either(),
                            )
                            ->map(static fn() => ($applied)($version))
                            ->leftMap(static fn($e) => match (true) {
                                $e instanceof Transaction\Failure => $e->unwrap(),
                                default => $e,
                            })
                            ->leftMap(static fn($e) => [$e, $applied]),
                        static fn($e) => Either::left([$e, $applied]),
                    ),
            );

        return new self($result);
    }

    /**
     * @template R
     *
     * @param callable(Sequence<Version>): R $successfully
     * @param callable(\Throwable, Sequence<Version>): R $failed
     *
     * @return R
     */
    public function match(callable $successfully, callable $failed): mixed
    {
        /** @psalm-suppress MixedArgument */
        return $this->result->match(
            static fn($versions) => $successfully($versions),
            static fn($error) => $failed($error[0], $error[1]),
        );
    }
}
