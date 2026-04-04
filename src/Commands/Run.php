<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\Immutable\{
    Map,
    Attempt,
    SideEffect,
};

final class Run
{
    private Processes $processes;
    /** @var callable(Reference): (callable(Command): Command) */
    private $configure;
    /** @var Map<Reference, Attempt<SideEffect>> */
    private Map $alreayRun;

    /**
     * @param callable(Reference): (callable(Command): Command) $configure
     */
    private function __construct(
        Processes $processes,
        callable $configure,
    ) {
        $this->processes = $processes;
        $this->configure = $configure;
        $this->alreayRun = Map::of();
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function __invoke(Command|Reference $command): Attempt
    {
        if ($command instanceof Command) {
            return $this
                ->processes
                ->execute($command)
                ->flatMap(
                    static fn($process) => $process
                        ->wait()
                        ->map(SideEffect::identity(...))
                        ->attempt(static fn($e) => new Failure($e)),
                );
        }

        $result = $this
            ->alreayRun
            ->get($command)
            ->match(
                static fn($result) => $result,
                fn() => $this
                    ->processes
                    ->execute(($this->configure)($command)($command->command()))
                    ->flatMap(
                        static fn($process) => $process
                            ->wait()
                            ->map(SideEffect::identity(...))
                            ->attempt(static fn($e) => new Failure($e)),
                    ),
            );
        $this->alreayRun = ($this->alreayRun)($command, $result);

        return $result;
    }

    /**
     * @param callable(Reference): (callable(Command): Command) $configure
     */
    public static function of(
        Processes $processes,
        callable $configure,
    ): self {
        return new self($processes, $configure);
    }
}
