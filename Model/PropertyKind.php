<?php

namespace Omnistay\Model;

/** What sort of place: a hotel's room or a home of one's own. */
enum PropertyKind: string
{
    case Hotel = 'hotel';
    case Guesthouse = 'guesthouse';
    case Apartment = 'apartment';
    case House = 'house';
    case Room = 'room';
    case Cabin = 'cabin';
    case Other = 'other';

    /** Rented by a person rather than run as a business: a home, a room, a cabin. */
    public function isPrivate(): bool
    {
        return \in_array($this, [self::Apartment, self::House, self::Room, self::Cabin], true);
    }
}
