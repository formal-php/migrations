<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\{
    Manager,
    Adapter\Transaction,
};
use Innmind\Time\Clock;
use Innmind\Immutable\{
    Sequence,
    Either,
    Attempt,
};

final readonly class Applied
{
    /**
     * @param Sequence<Version> $versions
     */
    private function __construct(
        private Sequence $versions,
    ) {
    }

    /**
     * @internal
     *
     * @param Sequence<callable(): Attempt<non-empty-string>> $migrations
     *
     * @return Either<Failure, self>
     */
    public static function of(
        Clock $clock,
        Manager $storage,
        Sequence $migrations,
    ): Either {
        $versions = $storage->repository(Version::class);
        /** @var Sequence<Version> */
        $applied = Sequence::of();

        return $migrations
            ->sink($applied)
            ->either(
                static fn($applied, $migrate) => $migrate()
                    ->map(static fn($name) => Version::new(
                        $name,
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
            )
            ->map(static fn($versions) => new self($versions))
            ->leftMap(static fn($tuple) => Failure::of(
                $tuple[0],
                $tuple[1],
            ));
    }

    /**
     * @return Sequence<Version>
     */
    public function versions(): Sequence
    {
        return $this->versions;
    }
}
