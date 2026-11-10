<?php

namespace Omnistay\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnistay\AvailabilityInterface;
use Omnistay\Model\Availability;
use Omnistay\Model\Guests;
use Omnistay\Model\Offer;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Stay;
use Omnistay\ProviderInterface;

/**
 * A provider's availability and offers behind its guard: each call is the operation of its
 * method's name ("availability", "offer").
 *
 * @extends Guarded<AvailabilityInterface>
 */
final class GuardedAvailability extends Guarded implements AvailabilityInterface
{
    public function __construct(AvailabilityInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function availability(PropertyRef $property, Stay $stay, Guests $guests, ?string $currency = null): Availability
    {
        return $this->guard->call('availability', fn () => $this->provider->availability($property, $stay, $guests, $currency));
    }

    public function offer(string $id, ?string $language = null): ?Offer
    {
        return $this->guard->call('offer', fn () => $this->provider->offer($id, $language));
    }
}
