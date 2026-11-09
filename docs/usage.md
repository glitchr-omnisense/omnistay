# Searching, pricing and booking

## Search

```php
$query = new StayQuery(
    Stay::of('2026-12-18', '2026-12-21'), new Guests(2, [6]),
    latitude: 45.899, longitude: 6.129, radius: 10,
    kinds: [PropertyKind::House, PropertyKind::Cabin], amenities: [Amenity::Kitchen],
    maxNightly: Money::of(15000, 'EUR'), minRating: 4.5,
);
$results = $registry->searchAll($query);          // every provider whose capabilities()->reads($query)
$results->items;                                  // SearchResult[], available first, then the cheapest night
$results->failures;                               // ['hotels' => 'Amadeus could not be reached for ...']
```

A provider reads a place as a text, an IATA city code or a point (`Capabilities::$searchBy`);
`searchAll()` asks only those that read the query's. The filters a provider cannot apply itself
are applied to its answers (`StayQuery::accepts()`).

## Availability, offers, calendar

```php
$availability = $registry->availabilityOf($ref, $stay, $guests);
if (!$availability->available) {
    echo $availability->reason;                    // booked, blocked, min_stay (see minStay), capacity, ...
}
$offer = $availability->cheapest();
$offer->rate->charges;                              // nights, cleaning, service, tourist tax ...
$offer = $registry->availability($ref->provider)->offer($offer->id);   // re-priced just before booking
$days = $registry->calendar('homes')->calendar($ref, new DateTimeImmutable('2026-12-01'), new DateTimeImmutable('2027-01-01'));
```

## Booking and cancelling

```php
$booking = $registry->bookOffer(new BookingRequest($offer, [$guest], paymentReference: $transaction->reference, clientReference: 'S-42'));
$booking->status;                                   // Confirmed, OnRequest (the host reviews), Pending
$registry->cancel('homes')->cancel($booking->reference);
```

Booked nights are never sold twice: a provider throws `UnavailableException` (`reason`: booked,
blocked, expired...) when the nights went meanwhile. omnistay/local holds them through its store,
which checks and writes under its own exclusion (omnibase/booking takes a Symfony lock).
