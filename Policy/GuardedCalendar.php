<?php

namespace Omnistay\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnistay\CalendarInterface;
use Omnistay\Model\PropertyRef;
use Omnistay\ProviderInterface;

/**
 * A provider's calendar behind its guard: each call is the operation
 * "calendar".
 *
 * @extends Guarded<CalendarInterface>
 */
final class GuardedCalendar extends Guarded implements CalendarInterface
{
    public function __construct(CalendarInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function calendar(PropertyRef $property, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->guard->call('calendar', fn () => $this->provider->calendar($property, $from, $to));
    }
}
