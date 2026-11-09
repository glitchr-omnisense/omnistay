<?php

namespace Omnistay\Model;

/**
 * Whether a property can take these guests on these dates, and the offers
 * it makes for them; when it cannot, $reason says why in the family's
 * words (booked, blocked, min_stay, max_stay, capacity, closed) and
 * $minStay how many nights it would take.
 */
final readonly class Availability
{
    /** @param list<Offer> $offers */
    public function __construct(
        public PropertyRef $property,
        public Stay $stay,
        public Guests $guests,
        public bool $available,
        public array $offers = [],
        public ?string $reason = null,
        public ?int $minStay = null,
    ) {
    }

    public function cheapest(): ?Offer
    {
        $offers = $this->offers;
        usort($offers, static fn (Offer $a, Offer $b) => $a->total()->amount <=> $b->total()->amount);

        return $offers[0] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['property' => (string) $this->property, 'stay' => $this->stay->toArray(), 'guests' => $this->guests->toArray(), 'available' => $this->available, 'offers' => array_map(static fn (Offer $o) => $o->toArray(), $this->offers), 'reason' => $this->reason, 'minStay' => $this->minStay];
    }
}
