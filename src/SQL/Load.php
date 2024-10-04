<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Innmind\Filesystem\{
    Adapter,
    File,
};
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

final class Load
{
    private function __construct()
    {
    }

    /**
     * @return Sequence<Migration>
     */
    public static function files(Adapter $filesystem): Sequence
    {
        return $filesystem
            ->root()
            ->all()
            ->keep(Instance::of(File::class))
            ->sort(static fn($a, $b) => $a->name()->toString() <=> $b->name()->toString())
            ->map(Migration::file(...));
    }
}
