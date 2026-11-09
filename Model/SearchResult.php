<?php

namespace Omnistay\Model;

/**
 * A property found, and what the stay asked would cost there: the lowest
 * total of its offers for those dates and guests (null when the search
 * had no dates, or the provider prices only on asking), and whether it is
 * free on those dates.
 */
final readonly class SearchResult
{
    public function __construct(
        public Property $property,
        public ?Money $total = null,
        public ?int $nights = null,
        public bool $available = true,
        public ?Offer $offer = null,
        public ?float $distance = null,
    ) {
    }

    /** The total spread over the nights: what a list of results shows. */
    public function nightly(): ?Money
    {
        return null !== $this->total && null !== $this->nights && $this->nights > 0 ? Money::of(intdiv($this->total->amount, $this->nights), $this->total->currency) : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['property' => $this->property->toArray(), 'total' => $this->total?->toArray(), 'nights' => $this->nights, 'nightly' => $this->nightly()?->toArray(), 'available' => $this->available, 'offer' => $this->offer?->id, 'distance' => $this->distance];
    }
}
