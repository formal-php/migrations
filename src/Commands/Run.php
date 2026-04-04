<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Process\Success,
};
use Innmind\Immutable\{
    Map,
    Either,
};

final class Run
{
    private Processes $processes;
    /** @var callable(Reference): (callable(Command): Command) */
    private $configure;
    /** @var Map<Reference, Either<Failure, Success>> */
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
     * @return Either<Failure, Success>
     */
    public function __invoke(Command|Reference $command): Either
    {
        if ($command instanceof Command) {
            return $this
                ->processes
                ->execute($command)
                ->unwrap()
                ->wait()
                ->leftMap(static fn($e) => new Failure($e));
        }

        $result = $this
            ->alreayRun
            ->get($command)
            ->match(
                static fn($result) => $result,
                fn() => $this
                    ->processes
                    ->execute(($this->configure)($command)($command->command()))
                    ->unwrap()
                    ->wait()
                    ->leftMap(static fn($e) => new Failure($e)),
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
