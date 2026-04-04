<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Innmind\Server\Control\Server\Process\{
    TimedOut,
    Failed,
    Signaled,
};

final class Failure extends \RuntimeException
{
    /**
     * @internal
     */
    public function __construct(
        private TimedOut|Failed|Signaled $error,
    ) {
        parent::__construct();
    }

    public function kind(): TimedOut|Failed|Signaled
    {
        return $this->error;
    }
}
