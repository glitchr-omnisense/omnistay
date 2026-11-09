<?php

namespace Omnistay\Model;

/**
 * Where a property is: a point (WGS 84) when known, and its address as
 * the provider gives it. $cityCode is the IATA code some providers search
 * by (PAR, NCE); $area a neighbourhood or a region shown instead of the
 * exact address before a booking (a private home's address stays hidden
 * until then: $exact says whether the point is the door or an
 * approximation).
 */
final readonly class Location
{
    /** @param list<string> $lines */
    public function __construct(
        public ?float $latitude = null,
        public ?float $longitude = null,
        public array $lines = [],
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $countryCode = null,
        public ?string $cityCode = null,
        public ?string $area = null,
        public bool $exact = true,
    ) {
    }

    public function hasPoint(): bool
    {
        return null !== $this->latitude && null !== $this->longitude;
    }

    /** The great-circle distance to a point, in kilometres; null without a point. */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if (null === $this->latitude || null === $this->longitude) {
            return null;
        }
        $rad = M_PI / 180;
        $a = sin(($latitude - $this->latitude) * $rad / 2) ** 2 + cos($this->latitude * $rad) * cos($latitude * $rad) * sin(($longitude - $this->longitude) * $rad / 2) ** 2;

        return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['latitude' => $this->latitude, 'longitude' => $this->longitude, 'lines' => $this->lines, 'city' => $this->city, 'postalCode' => $this->postalCode, 'countryCode' => $this->countryCode, 'cityCode' => $this->cityCode, 'area' => $this->area, 'exact' => $this->exact];
    }
}
