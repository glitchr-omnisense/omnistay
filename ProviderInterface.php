<?php

namespace Omnistay;

/**
 * One source of stays, configured: a hotel distribution system
 * (omnistay/amadeus), the site's own listings (omnistay/local)... Its
 * Capabilities say what it can be asked: search (SearchInterface), a
 * property's availability and offers (AvailabilityInterface), its
 * calendar (CalendarInterface), a booking (BookInterface), a cancellation
 * (CancelInterface), its description (ContentInterface) - and its usage
 * policy: its rate limits, what its terms ask of whoever shows its records.
 */
interface ProviderInterface extends \Omnisense\Contracts\Provider\ProviderInterface
{
    /** The name it is configured under in the registry ("hotels", "homes"): the provider of every PropertyRef it gives. */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}
