<?php

namespace Omnistay;

use Omnistay\Model\Booking;

interface CancelInterface
{
    /**
     * Cancels a booking: given back cancelled (a booking cancelled already
     * is given back as it is).
     *
     * @throws Exception\NotFoundException when the provider does not know it
     */
    public function cancel(string $reference, ?string $reason = null): Booking;
}
