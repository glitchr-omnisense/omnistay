<?php

namespace Omnistay\Model;

/**
 * A kind of room a property sells, or the whole home: its code there, a
 * name, how many sleep in it and on what. A private rental is one
 * "room type": the place itself.
 */
final readonly class RoomType
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public ?int $maxGuests = null,
        public ?int $beds = null,
        public ?string $bedType = null,
        public ?int $bedrooms = null,
        public ?float $bathrooms = null,
        public ?int $area = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'maxGuests' => $this->maxGuests, 'beds' => $this->beds, 'bedType' => $this->bedType, 'bedrooms' => $this->bedrooms, 'bathrooms' => $this->bathrooms, 'area' => $this->area];
    }
}
