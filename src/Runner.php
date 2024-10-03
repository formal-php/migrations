<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Innmind\Immutable\Sequence;

/**
 * @template T
 * @template E
 */
interface Runner
{
    /**
     * @param Sequence<Migration<T, E>> $migrations
     */
    public function __invoke(Sequence $migrations): Applied;
}
