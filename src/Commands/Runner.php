<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Formal\Migrations\{
    Applied,
    Migrations\All,
};
use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};

/**
 * @internal
 */
final class Runner
{
    private Manager $storage;
    private OperatingSystem $os;
    /** @var callable(OperatingSystem): Processes */
    private $build;
    /** @var callable(Reference): (callable(Command): Command) */
    private $configure;

    /**
     * @param callable(OperatingSystem): Processes $build
     * @param callable(Reference): (callable(Command): Command) $configure
     */
    private function __construct(
        Manager $storage,
        OperatingSystem $os,
        callable $build,
        callable $configure,
    ) {
        $this->storage = $storage;
        $this->os = $os;
        $this->build = $build;
        $this->configure = $configure;
    }

    /**
     * @param All<Run> $migrations
     */
    public function __invoke(All $migrations): Applied
    {
        $processes = ($this->build)($this->os);
        $run = Run::of($processes, $this->configure);

        return Applied::of(
            $this->os->clock(),
            $this->storage,
            $migrations
                ->excludeAlreadyApplied($this->storage)
                ->map(static fn($migration) => static fn() => $migration($run)->map(
                    static fn() => $migration->name(),
                )),
        );
    }

    /**
     * @internal
     *
     * @param ?callable(OperatingSystem): Processes $build
     * @param ?callable(Reference): (callable(Command): Command) $configure
     */
    public static function of(
        Manager $storage,
        OperatingSystem $os,
        ?callable $build = null,
        ?callable $configure = null,
    ): self {
        return new self(
            $storage,
            $os,
            $build ?? static fn(OperatingSystem $os) => $os->control()->processes(),
            $configure ?? static fn(Reference $ref) => static fn(Command $command) => $command,
        );
    }
}
