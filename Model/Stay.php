<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/**
 * The dates of a stay: the night of the check-in to the morning of the
 * check-out, local dates at the property (no time, no time zone that
 * matters: both are read at midnight UTC).
 */
final readonly class Stay
{
    public \DateTimeImmutable $checkIn;
    public \DateTimeImmutable $checkOut;

    public function __construct(\DateTimeInterface|string $checkIn, \DateTimeInterface|string $checkOut)
    {
        $this->checkIn = self::day($checkIn);
        $this->checkOut = self::day($checkOut);
        if ($this->checkOut <= $this->checkIn) {
            throw new InvalidArgumentException(\sprintf('A stay ends after it starts: %s to %s.', $this->checkIn->format('Y-m-d'), $this->checkOut->format('Y-m-d')));
        }
    }

    public static function of(string $checkIn, string $checkOut): self
    {
        return new self($checkIn, $checkOut);
    }

    /** A stay of $nights nights from $checkIn. */
    public static function nights(\DateTimeInterface|string $checkIn, int $nights): self
    {
        $from = self::day($checkIn);

        return new self($from, $from->modify(\sprintf('+%d days', max(1, $nights))));
    }

    public function count(): int
    {
        return (int) $this->checkIn->diff($this->checkOut)->days;
    }

    /**
     * Each night, by the date it starts: a stay from the 3rd to the 5th has
     * the nights of the 3rd and the 4th.
     *
     * @return list<\DateTimeImmutable>
     */
    public function dates(): array
    {
        $dates = [];
        for ($day = $this->checkIn; $day < $this->checkOut; $day = $day->modify('+1 day')) {
            $dates[] = $day;
        }

        return $dates;
    }

    /** Whether the two stays share a night (a check-out on the other's check-in day shares none). */
    public function overlaps(self $other): bool
    {
        return $this->checkIn < $other->checkOut && $other->checkIn < $this->checkOut;
    }

    public function contains(\DateTimeInterface $night): bool
    {
        $night = self::day($night);

        return $night >= $this->checkIn && $night < $this->checkOut;
    }

    public function equals(self $other): bool
    {
        return $this->checkIn == $other->checkIn && $this->checkOut == $other->checkOut;
    }

    /** @return array{checkIn: string, checkOut: string, nights: int} */
    public function toArray(): array
    {
        return ['checkIn' => $this->checkIn->format('Y-m-d'), 'checkOut' => $this->checkOut->format('Y-m-d'), 'nights' => $this->count()];
    }

    public function __toString(): string
    {
        return $this->checkIn->format('Y-m-d').'/'.$this->checkOut->format('Y-m-d');
    }

    /** A date as the family keeps one: midnight UTC of the day written. */
    public static function day(\DateTimeInterface|string $date): \DateTimeImmutable
    {
        $text = \is_string($date) ? trim($date) : $date->format('Y-m-d');
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($text, 0, 10), new \DateTimeZone('UTC'));
        if (false === $day || $day->format('Y-m-d') !== substr($text, 0, 10)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a date (YYYY-MM-DD).', $text));
        }

        return $day;
    }
}
