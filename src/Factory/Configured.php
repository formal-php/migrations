<?php
declare(strict_types = 1);

namespace Formal\Migrations\Factory;

use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Configured
{
    /**
     * @param \Closure(): Attempt<SideEffect> $setup
     */
    private function __construct(
        private OperatingSystem $os,
        private Manager $storage,
        private \Closure $setup,
    ) {
    }

    /**
     * @internal
     *
     * @param ?\Closure(): Attempt<SideEffect> $setup
     */
    public static function of(
        OperatingSystem $os,
        Manager $storage,
        ?\Closure $setup = null,
    ): self {
        return new self($os, $storage, $setup ?? static fn() => Attempt::result(SideEffect::identity));
    }

    public function sql(): SQL
    {
        return SQL::new($this->os, $this->storage, $this->setup);
    }

    public function commands(): Commands
    {
        return Commands::new($this->os, $this->storage, $this->setup);
    }
}
