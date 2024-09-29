<?php
declare(strict_types = 1);

namespace Formal\Migrations;

/**
 * @template T
 */
interface Migration
{
    /**
     * @param T $kind
     */
    public function __invoke($kind): void;

    /**
     * @return non-empty-string
     */
    public function name(): string;
}
