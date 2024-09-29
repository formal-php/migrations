<?php
declare(strict_types = 1);

namespace Fixtures\Formal\Migrations;

use Formal\Migrations\Commands\Reference;
use Innmind\Server\Control\Server\Command;

enum Ref implements Reference
{
    case rm;

    public function command(): Command
    {
        return Command::foreground('rm test');
    }
}
