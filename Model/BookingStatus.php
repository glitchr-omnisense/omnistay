<?php

namespace Omnistay\Model;

/** Where a booking stands, whatever the provider calls it. */
enum BookingStatus: string
{
    /** Held, waiting for its payment or for the host to accept it. */
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    /** Asked to a property that answers later (a hotel "on request", a host who reviews requests). */
    case OnRequest = 'on_request';
    case Cancelled = 'cancelled';
    case Refused = 'refused';
    case Completed = 'completed';

    /** Whether it still holds its nights. */
    public function holds(): bool
    {
        return \in_array($this, [self::Pending, self::Confirmed, self::OnRequest, self::Completed], true);
    }
}
