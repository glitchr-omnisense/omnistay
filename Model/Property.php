<?php

namespace Omnistay\Model;

/**
 * A place to stay - a hotel or a home rented by its owner - as a provider
 * describes it: its name, kind and location, its words, what it offers,
 * its pictures, the rooms it sells, its stars and the guests' score. A
 * search may give it short (a name and a point); ContentInterface gives
 * it whole.
 */
final readonly class Property
{
    /**
     * @param list<Amenity>  $amenities
     * @param list<string>   $otherAmenities the provider's codes with no Amenity
     * @param list<Photo>    $photos
     * @param list<RoomType> $roomTypes
     */
    public function __construct(
        public PropertyRef $ref,
        public string $name,
        public PropertyKind $kind,
        public Location $location,
        public ?string $description = null,
        public array $amenities = [],
        public array $otherAmenities = [],
        public array $photos = [],
        public array $roomTypes = [],
        public ?int $stars = null,
        public ?float $rating = null,
        public int $reviews = 0,
        public ?string $host = null,
        public ?string $checkInFrom = null,
        public ?string $checkOutUntil = null,
        public ?int $maxGuests = null,
        public ?string $chain = null,
        public ?string $url = null,
    ) {
    }

    public function has(Amenity $amenity): bool
    {
        return \in_array($amenity, $this->amenities, true);
    }

    public function photo(): ?Photo
    {
        return $this->photos[0] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'ref' => (string) $this->ref,
            'name' => $this->name,
            'kind' => $this->kind->value,
            'location' => $this->location->toArray(),
            'description' => $this->description,
            'amenities' => array_map(static fn (Amenity $a) => $a->value, $this->amenities),
            'otherAmenities' => $this->otherAmenities,
            'photos' => array_map(static fn (Photo $p) => $p->toArray(), $this->photos),
            'roomTypes' => array_map(static fn (RoomType $r) => $r->toArray(), $this->roomTypes),
            'stars' => $this->stars,
            'rating' => $this->rating,
            'reviews' => $this->reviews,
            'host' => $this->host,
            'checkInFrom' => $this->checkInFrom,
            'checkOutUntil' => $this->checkOutUntil,
            'maxGuests' => $this->maxGuests,
            'chain' => $this->chain,
            'url' => $this->url,
        ];
    }
}
