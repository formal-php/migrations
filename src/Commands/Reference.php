<?php
declare(strict_types = 1);

namespace Formal\Migrations\Commands;

use Innmind\Server\Control\Server\Command;

interface Reference extends \UnitEnum
{
    public function command(): Command;
}
