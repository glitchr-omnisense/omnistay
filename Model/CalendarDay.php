<?php

namespace Omnistay\Model;

/**
 * One night of a property's calendar: whether it is free, why not
 * (booked, blocked), its price, and the shortest stay that may start on
 * it.
 */
final readonly class CalendarDay
{
    public function __construct(
        public \DateTimeImmutable $date,
        public bool $available,
        public ?Money $price = null,
        public int $minStay = 1,
        public ?string $reason = null,
        public bool $checkInAllowed = true,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['date' => $this->date->format('Y-m-d'), 'available' => $this->available, 'price' => $this->price?->toArray(), 'minStay' => $this->minStay, 'reason' => $this->reason, 'checkInAllowed' => $this->checkInAllowed];
    }
}
