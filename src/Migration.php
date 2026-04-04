<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Innmind\Immutable\{
    SideEffect,
    Attempt,
};

/**
 * @template T
 */
interface Migration
{
    /**
     * @param T $kind
     *
     * @return Attempt<SideEffect>
     */
    public function __invoke($kind): Attempt;

    /**
     * @return non-empty-string
     */
    public function name(): string;
}
