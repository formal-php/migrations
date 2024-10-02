<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Innmind\Immutable\Sequence;

/**
 * @template T
 */
interface Runner
{
    /**
     * @param Sequence<Migration<T>> $migrations
     */
    public function __invoke(Sequence $migrations): Applied;
}
