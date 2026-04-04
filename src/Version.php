<?php
declare(strict_types = 1);

namespace Formal\Migrations;

use Formal\ORM\Id;
use Innmind\Time\{
    Clock,
    Point,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Version
{
    /** @var Id<self> */
    private Id $id;
    /** @var non-empty-string */
    private string $name;
    private Point $appliedAt;

    /**
     * @param Id<self> $id
     * @param non-empty-string $name
     */
    private function __construct(
        Id $id,
        string $name,
        Point $appliedAt,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->appliedAt = $appliedAt;
    }

    /**
     * @param non-empty-string $name
     */
    public static function new(string $name, Clock $clock): self
    {
        return new self(
            Id::new(self::class),
            $name,
            $clock->now(),
        );
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function appliedAt(): Point
    {
        return $this->appliedAt;
    }
}
