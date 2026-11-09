<?php

namespace Omnistay;

use Omnistay\Model\Availability;
use Omnistay\Model\Guests;
use Omnistay\Model\Offer;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Stay;

interface AvailabilityInterface
{
    /** Whether the property takes these guests on these dates, and its offers for them, priced. */
    public function availability(PropertyRef $property, Stay $stay, Guests $guests, ?string $currency = null): Availability;

    /**
     * An offer as it stands now - re-priced, its policies whole - before it
     * is booked; null when it is gone (expired, sold out).
     */
    public function offer(string $id, ?string $language = null): ?Offer;
}
