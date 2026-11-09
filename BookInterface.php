<?php

namespace Omnistay;

use Omnistay\Model\Booking;
use Omnistay\Model\BookingRequest;

interface BookInterface
{
    /**
     * Books the request's offer. Confirmed, pending or on request, as the
     * provider answers.
     *
     * @throws Exception\UnavailableException when the nights are taken meanwhile
     * @throws Exception\ProviderException    when the provider refuses it otherwise
     */
    public function book(BookingRequest $request): Booking;

    /** A booking by its reference at the provider; null when it does not know it. */
    public function booking(string $reference): ?Booking;
}
