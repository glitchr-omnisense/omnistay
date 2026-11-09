<?php

namespace Omnistay\Model;

/**
 * What a place offers, in the family's words; a provider maps its own
 * codes to these (Amadeus' WIFI, SWIMMING_POOL...) and keeps what has no
 * match in Property::$otherAmenities.
 */
enum Amenity: string
{
    case Wifi = 'wifi';
    case Kitchen = 'kitchen';
    case Parking = 'parking';
    case Pool = 'pool';
    case AirConditioning = 'air_conditioning';
    case Heating = 'heating';
    case Washer = 'washer';
    case Workspace = 'workspace';
    case Pets = 'pets';
    case Breakfast = 'breakfast';
    case Accessible = 'accessible';
    case Elevator = 'elevator';
    case Fireplace = 'fireplace';
    case Garden = 'garden';
    case Spa = 'spa';
    case Gym = 'gym';
    case Restaurant = 'restaurant';
    case Reception = 'reception';
    case EvCharger = 'ev_charger';
    case SeaView = 'sea_view';

    /**
     * @param iterable<string> $values
     *
     * @return list<self> the values known, in the order given, once each
     */
    public static function list(iterable $values): array
    {
        $list = [];
        foreach ($values as $value) {
            $amenity = self::tryFrom(strtolower(trim($value)));
            if (null !== $amenity && !\in_array($amenity, $list, true)) {
                $list[] = $amenity;
            }
        }

        return $list;
    }
}
