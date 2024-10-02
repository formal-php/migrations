<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Innmind\Immutable\{
    SideEffect,
    Maybe,
};

/**
 * @template T
 */
interface Migration
{
    /**
     * @param T $kind
     *
     * @return Maybe<SideEffect>
     */
    public function __invoke($kind): Maybe;

    /**
     * @return non-empty-string
     */
    public function name(): string;
}
