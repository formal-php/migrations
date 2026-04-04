<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Innmind\Immutable\Sequence;

final class Failure
{
    /**
     * @param Sequence<Version> $versions
     */
    private function __construct(
        private \Throwable $error,
        private Sequence $versions,
    ) {
    }

    /**
     * @internal
     *
     * @param Sequence<Version> $versions
     */
    public static function of(
        \Throwable $error,
        Sequence $versions,
    ): self {
        return new self($error, $versions);
    }

    public function error(): \Throwable
    {
        return $this->error;
    }

    /**
     * @return Sequence<Version>
     */
    public function applied(): Sequence
    {
        return $this->versions;
    }
}
