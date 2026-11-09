<?php

namespace Omnistay\Testing;

use Omnistay\AvailabilityInterface;
use Omnistay\BookInterface;
use Omnistay\CalendarInterface;
use Omnistay\CancelInterface;
use Omnistay\Capabilities;
use Omnistay\ContentInterface;
use Omnistay\Exception\NotFoundException;
use Omnistay\Exception\UnavailableException;
use Omnistay\Model\Availability;
use Omnistay\Model\Booking;
use Omnistay\Model\BookingRequest;
use Omnistay\Model\BookingStatus;
use Omnistay\Model\CalendarDay;
use Omnistay\Model\Charge;
use Omnistay\Model\ChargeKind;
use Omnistay\Model\Guests;
use Omnistay\Model\Money;
use Omnistay\Model\NightRate;
use Omnistay\Model\Offer;
use Omnistay\Model\Page;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyKind;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Rate;
use Omnistay\Model\SearchResult;
use Omnistay\Model\Stay;
use Omnistay\Model\StayQuery;
use Omnistay\ProviderInterface;
use Omnistay\SearchInterface;

/**
 * A provider that answers every contract from the properties it was given,
 * with no network: an application's tests, the harness's bare script. Each
 * property has one price per night; a night given as blocked, or held by a
 * booking it made, is not free. Its offers' ids are
 * "<property id>|<check-in>|<check-out>|<adults>", its bookings' references
 * "FX-1", "FX-2"... A page's token is the offset of its first item. It holds
 * the properties whose ref names it (its name in the registry): one factory
 * given hotels under "hotels" and homes under "homes" serves two providers.
 */
final class FixedProvider implements ProviderInterface, SearchInterface, AvailabilityInterface, CalendarInterface, BookInterface, CancelInterface, ContentInterface
{
    /** @var array<string, Booking> */
    private array $bookings = [];

    /**
     * @param list<Property>                $properties their refs' provider is this provider's name
     * @param array<string, Money>          $nightly    by property id
     * @param array<string, list<string>>   $blocked    nights (Y-m-d) by property id
     */
    public function __construct(private readonly array $properties = [], private readonly array $nightly = [], private readonly array $blocked = [], private readonly string $name = 'fixed')
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return 'Fixed stays';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(search: true, availability: true, calendar: true, book: true, cancel: true, content: true, searchBy: ['place', 'cityCode', 'point'], kinds: PropertyKind::cases(), pricedSearch: true, keyless: true, sandbox: true);
    }

    public function search(StayQuery $query): Page
    {
        $found = [];
        foreach ($this->mine() as $property) {
            $location = $property->location;
            $distance = $query->hasPoint() ? $location->distanceTo((float) $query->latitude, (float) $query->longitude) : null;
            $text = mb_strtolower(implode(' ', array_filter([$property->name, $location->city, $location->area, $location->countryCode])));
            $matches = (null !== $query->place && str_contains($text, mb_strtolower(trim($query->place))))
                || (null !== $query->cityCode && strtoupper($query->cityCode) === $location->cityCode)
                || (null !== $distance && $distance <= $query->radius);
            if (!$matches) {
                continue;
            }
            $availability = null !== $query->stay ? $this->availability($property->ref, $query->stay, $query->guests) : null;
            $offer = $availability?->cheapest();
            $found[] = new SearchResult($property, $offer?->total(), $query->stay?->count(), $availability->available ?? true, $offer, $distance);
        }
        $offset = max(0, (int) $query->page);

        return new Page(\array_slice($found, $offset, $query->limit), $offset + $query->limit < \count($found) ? (string) ($offset + $query->limit) : null, \count($found));
    }

    public function availability(PropertyRef $property, Stay $stay, Guests $guests, ?string $currency = null): Availability
    {
        $known = $this->find($property->id);
        if (null === $known) {
            throw new NotFoundException(\sprintf('No property "%s".', $property));
        }
        if (null !== $known->maxGuests && $guests->count() > $known->maxGuests) {
            return new Availability($property, $stay, $guests, false, reason: 'capacity');
        }
        foreach ($stay->dates() as $night) {
            if (null !== $reason = $this->taken($property->id, $night)) {
                return new Availability($property, $stay, $guests, false, reason: $reason);
            }
        }

        return new Availability($property, $stay, $guests, true, [$this->quote($known, $stay, $guests)]);
    }

    public function offer(string $id, ?string $language = null): ?Offer
    {
        $parts = explode('|', $id);
        if (4 !== \count($parts) || null === $property = $this->find($parts[0])) {
            return null;
        }
        $availability = $this->availability($property->ref, Stay::of($parts[1], $parts[2]), new Guests(max(1, (int) $parts[3])));

        return $availability->offers[0] ?? null;
    }

    public function calendar(PropertyRef $property, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $days = [];
        $price = $this->nightly[$property->id] ?? null;
        for ($day = Stay::day($from); $day < Stay::day($to); $day = $day->modify('+1 day')) {
            $reason = $this->taken($property->id, $day);
            $days[] = new CalendarDay($day, null === $reason, $price, 1, $reason);
        }

        return $days;
    }

    public function book(BookingRequest $request): Booking
    {
        $asked = $request->offer;
        $availability = $this->availability($asked->property, $asked->stay, $asked->guests);
        $offer = $availability->offers[0] ?? throw UnavailableException::because($availability->reason ?? 'expired', (string) $asked->property, (string) $asked->stay);
        $reference = 'FX-'.(\count($this->bookings) + 1);

        return $this->bookings[$reference] = new Booking($reference, BookingStatus::Confirmed, $offer->property, $offer->stay, $offer->guests, $offer->total(), [$reference], $request->travellers, $offer, $offer->cancellation, new \DateTimeImmutable('now', new \DateTimeZone('UTC')), $request->clientReference);
    }

    public function booking(string $reference): ?Booking
    {
        return $this->bookings[$reference] ?? null;
    }

    public function cancel(string $reference, ?string $reason = null): Booking
    {
        $booking = $this->bookings[$reference] ?? throw new NotFoundException(\sprintf('No booking "%s".', $reference));

        return $this->bookings[$reference] = $booking->withStatus(BookingStatus::Cancelled);
    }

    public function property(PropertyRef $property, ?string $language = null): ?Property
    {
        return $this->find($property->id);
    }

    public function properties(array $properties, ?string $language = null): array
    {
        return array_values(array_filter(array_map(fn (PropertyRef $ref) => $this->find($ref->id), $properties)));
    }

    /** @return list<Property> */
    private function mine(): array
    {
        return array_values(array_filter($this->properties, fn (Property $property) => $this->name === $property->ref->provider));
    }

    private function find(string $id): ?Property
    {
        foreach ($this->mine() as $property) {
            if ($id === $property->ref->id) {
                return $property;
            }
        }

        return null;
    }

    private function taken(string $id, \DateTimeImmutable $night): ?string
    {
        if (\in_array($night->format('Y-m-d'), $this->blocked[$id] ?? [], true)) {
            return 'blocked';
        }
        foreach ($this->bookings as $booking) {
            if ($id === $booking->property->id && $booking->status->holds() && $booking->stay->contains($night)) {
                return 'booked';
            }
        }

        return null;
    }

    private function quote(Property $property, Stay $stay, Guests $guests): Offer
    {
        $price = $this->nightly[$property->ref->id] ?? Money::of(10000, 'EUR');
        $nights = array_map(static fn (\DateTimeImmutable $date) => new NightRate($date, $price), $stay->dates());
        $total = $price->multiply($stay->count());
        $id = implode('|', [$property->ref->id, $stay->checkIn->format('Y-m-d'), $stay->checkOut->format('Y-m-d'), $guests->adults]);

        return new Offer($id, $property->ref, $stay, $guests, new Rate($total, [new Charge(ChargeKind::Nights, $total)], $nights), roomType: $property->roomTypes[0] ?? null);
    }
}
