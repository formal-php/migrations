<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\Migrations\Commands\{
    Run,
    Reference,
};
use Formal\ORM\Manager;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};
use Innmind\Immutable\Sequence;

final class Commands
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
     * @param Sequence<Migration<Run>> $migrations
     */
    public function __invoke(Sequence $migrations): Applied
    {
        $versions = $this->storage->repository(Version::class);
        $processes = ($this->build)($this->os);
        $run = Run::of($processes, $this->configure);

        return $migrations
            ->exclude(static fn($migration) => $versions->any(
                Property::of(
                    'name',
                    Sign::equality,
                    $migration->name(),
                ),
            ))
            ->reduce(
                Applied::new($this->os->clock(), $this->storage),
                static fn(Applied $applied, $migration) => $applied->then(
                    $run,
                    $migration,
                ),
            );
    }

    /**
     * @param ?callable(OperatingSystem): Processes $build
     * @param ?callable(Reference): (callable(Command): Command) $configure
     */
    public static function of(
        Manager $storage,
        OperatingSystem $os,
        callable $build = null,
        callable $configure = null,
    ): self {
        return new self(
            $storage,
            $os,
            $build ?? static fn(OperatingSystem $os) => $os->control()->processes(),
            $configure ?? static fn(Reference $ref) => static fn(Command $command) => $command,
        );
    }
}
