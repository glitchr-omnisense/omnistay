<?php

namespace Omnistay\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnistay\BookInterface;
use Omnistay\Model\Booking;
use Omnistay\Model\BookingRequest;
use Omnistay\ProviderInterface;

/**
 * A provider's bookings behind its guard: each call is the operation of its
 * method's name ("book", "booking").
 *
 * @extends Guarded<BookInterface>
 */
final class GuardedBook extends Guarded implements BookInterface
{
    public function __construct(BookInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function book(BookingRequest $request): Booking
    {
        return $this->guard->call('book', fn () => $this->provider->book($request));
    }

    public function booking(string $reference): ?Booking
    {
        return $this->guard->call('booking', fn () => $this->provider->booking($reference));
    }
}
