<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Innmind\Immutable\{
    SideEffect,
    Either,
};

/**
 * @template T
 * @template E
 */
interface Migration
{
    /**
     * @param T $kind
     *
     * @return Either<E, SideEffect>
     */
    public function __invoke($kind): Either;

    /**
     * @return non-empty-string
     */
    public function name(): string;
}
