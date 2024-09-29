<?php
declare(strict_types = 1);

namespace Formal\Migrations\SQL;

use Formal\AccessLayer\Query;
use Innmind\Immutable\{
    Str,
    Sequence,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Line
{
    private function __construct(
        private bool $comment,
        private Str $line,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function parse(Str $line): self
    {
        return match ($line->startsWith('--')) {
            true => self::comment(),
            false => self::of($line),
        };
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function comment(): self
    {
        return new self(true, Str::of(''));
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function of(Str $line): self
    {
        return new self(false, $line);
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @return Sequence<self>
     */
    public static function window(self $a, self $b): Sequence
    {
        if ($b->comment) {
            return Sequence::of($a, $b);
        }

        return Sequence::of(new self(
            false,
            match ($a->comment) {
                true => $b->line,
                false => $a->line->append(' ')->append($b->line),
            },
        ));
    }

    public function query(): ?Query
    {
        $query = $this->line->toString();

        return match ($query) {
            '' => null,
            default => Query\SQL::of($query),
        };
    }
}
