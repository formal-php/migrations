<?php
declare(strict_types = 1);

namespace Formal\Migrations\Factory;

use Formal\Migrations\{
    Commands as Runner,
    Applied,
    Migration,
};
use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\Immutable\Sequence;

final readonly class Commands
{
    /**
     * @param \Closure(): void $setup
     * @param Sequence<Migration<Runner\Run>> $migrations
     */
    private function __construct(
        private OperatingSystem $os,
        private Manager $storage,
        private \Closure $setup,
        private Sequence $migrations,
    ) {
    }

    /**
     * @internal
     *
     * @param \Closure(): void $setup
     */
    public static function new(
        OperatingSystem $os,
        Manager $storage,
        \Closure $setup,
    ): self {
        return new self($os, $storage, $setup, Sequence::of());
    }

    /**
     * @param Sequence<Migration<Runner\Run>> $migrations
     */
    public function of(Sequence $migrations): self
    {
        return new self(
            $this->os,
            $this->storage,
            $this->setup,
            $migrations,
        );
    }

    /**
     * @param ?callable(OperatingSystem): Processes $build
     * @param ?callable(Runner\Reference): (callable(Command): Command) $configure
     */
    public function migrate(
        callable $build = null,
        callable $configure = null,
    ): Applied {
        ($this->setup)();

        return Runner::of(
            $this->storage,
            $this->os,
            $build,
            $configure,
        )($this->migrations);
    }
}
