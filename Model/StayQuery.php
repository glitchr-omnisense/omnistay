<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/**
 * Where, when and for whom - and what to keep of the answers. A place is
 * a text ($place: a town, a region, a word of the name), an IATA city
 * code ($cityCode), or a point with a radius in kilometres; a provider
 * reads what it can (CapabilitiesInterface says which) and the filters it
 * cannot apply itself are applied to its answers by the registry
 * (StayQuery::accepts()).
 */
final readonly class StayQuery
{
    /**
     * @param list<PropertyKind> $kinds     none: every kind
     * @param list<Amenity>      $amenities each of them
     */
    public function __construct(
        public ?Stay $stay = null,
        public Guests $guests = new Guests(),
        public ?string $place = null,
        public ?string $cityCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public float $radius = 10.0,
        public array $kinds = [],
        public array $amenities = [],
        public ?Money $minNightly = null,
        public ?Money $maxNightly = null,
        public ?float $minRating = null,
        public ?string $currency = null,
        public ?string $language = null,
        public int $rooms = 1,
        public int $limit = 20,
        public ?string $page = null,
    ) {
        if (null !== $minNightly && null !== $maxNightly && $minNightly->currency !== $maxNightly->currency) {
            throw new InvalidArgumentException('A price range is in one currency.');
        }
        if ((null === $latitude) !== (null === $longitude)) {
            throw new InvalidArgumentException('A point has a latitude and a longitude.');
        }
        if ($rooms < 1 || $limit < 1 || $radius <= 0) {
            throw new InvalidArgumentException('At least one room, one answer, and a radius.');
        }
    }

    public function hasPoint(): bool
    {
        return null !== $this->latitude && null !== $this->longitude;
    }

    /** The same query asking for another page of answers. */
    public function withPage(?string $page): self
    {
        return new self($this->stay, $this->guests, $this->place, $this->cityCode, $this->latitude, $this->longitude, $this->radius, $this->kinds, $this->amenities, $this->minNightly, $this->maxNightly, $this->minRating, $this->currency, $this->language, $this->rooms, $this->limit, $page);
    }

    /**
     * Whether a result passes the filters: kind, amenities, rating, the
     * guests it can sleep, the radius around the point, the nightly price
     * (in the range's currency; a result priced in another is kept).
     */
    public function accepts(SearchResult $result): bool
    {
        $property = $result->property;
        if ($this->kinds && !\in_array($property->kind, $this->kinds, true)) {
            return false;
        }
        foreach ($this->amenities as $amenity) {
            if (!$property->has($amenity)) {
                return false;
            }
        }
        if (null !== $this->minRating && (null === $property->rating || $property->rating < $this->minRating)) {
            return false;
        }
        if (null !== $property->maxGuests && $property->maxGuests < $this->guests->count()) {
            return false;
        }
        if ($this->hasPoint() && null !== $distance = $property->location->distanceTo((float) $this->latitude, (float) $this->longitude)) {
            if ($distance > $this->radius) {
                return false;
            }
        }
        $nightly = $result->nightly();
        if (null !== $nightly) {
            if (null !== $this->minNightly && $nightly->currency === $this->minNightly->currency && $nightly->amount < $this->minNightly->amount) {
                return false;
            }
            if (null !== $this->maxNightly && $nightly->currency === $this->maxNightly->currency && $nightly->amount > $this->maxNightly->amount) {
                return false;
            }
        }

        return true;
    }
}
