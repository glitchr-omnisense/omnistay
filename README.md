# omnisense/omnistay

Where to stay and what to book for a trip, under any PHP host, whoever sells it. Hotels from a
distribution system and homes rented by their owners answer the same contracts, so a site searches
both through one interface, prices a stay line by line, books it and cancels it.

## Install and a first call

```sh
composer require omnisense/omnistay omnistay/local
```

No account and no network for this first call: `omnistay/local` answers from a site's own
listings, here one home kept in memory (`InMemoryStore`), searched, then priced line by line.
Hotels through `omnistay/amadeus` need an Amadeus Enterprise contract (a client id and secret).

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnistay\Local\{InMemoryStore, Listing, LocalFactory, Pricing};
use Omnistay\Model\{Guests, Location, Money, Property, PropertyKind, PropertyRef, Stay, StayQuery};
use Omnistay\Registry;

$store = new InMemoryStore([new Listing(
    new Property(new PropertyRef('homes', 'L-1'), 'Cabane des Tilleuls', PropertyKind::Cabin, new Location(45.91, 6.14, city: 'Annecy')),
    new Pricing(Money::of(9000, 'EUR'), cleaning: Money::of(4000, 'EUR')),   // 90 EUR a night, 40 EUR cleaning
)]);
$registry = new Registry([new LocalFactory($store)], ['homes' => ['factory' => 'local', 'options' => ['service_fee' => 12]]]);

$stay = Stay::of(date('Y-m-d', strtotime('+30 days')), date('Y-m-d', strtotime('+33 days')));
$results = $registry->searchAll(new StayQuery($stay, new Guests(2), latitude: 45.9, longitude: 6.13, radius: 10));
$home = $results->items[0];
echo $home->property->name, ', ', $home->nightly(), " a night, all in\n";         // Cabane des Tilleuls, 115.73 EUR a night, all in
$offer = $registry->availabilityOf($home->property->ref, $stay, new Guests(2))->cheapest();
foreach ($offer->rate->charges as $charge) {
    echo $charge->code, ' ', $charge->amount, "\n";                                // nights 270.00 EUR, cleaning 40.00 EUR, service 37.20 EUR
}
echo 'total ', $offer->total(), "\n";                                              // total 347.20 EUR
```

With hotels and homes together:

```php
use Omnistay\Amadeus\AmadeusFactory;
use Omnistay\Local\LocalFactory;
use Omnistay\Model\{Guests, Stay, StayQuery};
use Omnistay\Registry;

$registry = new Registry([new AmadeusFactory($http), new LocalFactory($store)], [
    'hotels' => ['factory' => 'amadeus', 'options' => ['client_id' => getenv('AMADEUS_CLIENT_ID'), 'client_secret' => getenv('AMADEUS_CLIENT_SECRET')]],
    'homes' => ['factory' => 'local', 'options' => ['service_fee' => 12]],
]);

$results = $registry->searchAll(new StayQuery(Stay::of('2026-12-18', '2026-12-21'), new Guests(2), latitude: 45.899, longitude: 6.129, radius: 10));
foreach ($results->items as $result) {
    echo $result->property->name, ' ', $result->nightly(), "\n";   // hotels and homes, the cheapest night first
}
$offer = $registry->availabilityOf($results->items[0]->property->ref, $stay, $guests)->cheapest();
$booking = $registry->bookOffer(new BookingRequest($offer, [new Traveller('Alice', 'Example', 'alice@example.org')], paymentReference: $paid));
```

## The shape

- **Models** (`Omnistay\Model`): `Property` (a hotel or a home: kind, `Location`, `Amenity`s,
  `Photo`s, `RoomType`s, stars, guests' rating), `Stay` and `Guests`, `StayQuery`, `SearchResult`,
  `Offer` with its `Rate` (the nights one by one, `Charge`s: cleaning, service, taxes, discounts),
  `CancellationPolicy` and `PaymentTiming`, `Availability`, `CalendarDay`, `BookingRequest`,
  `Booking` and `BookingStatus`, `Money`; `CarHire` and `Package`, shared models for the providers
  that sell them.
- **Contracts by capability**, each provider saying which it has (`Capabilities`):
  `SearchInterface`, `AvailabilityInterface` (a property's offers, an offer re-priced),
  `CalendarInterface`, `BookInterface`, `CancelInterface`, `ContentInterface`.
- `Registry` (providers by name; `searchAll()` merges several providers' answers; a `PropertyRef`
  names the provider it came from, so every question about it goes back there), `Config`,
  `ProviderFactory`, as in the other Omnisense families.
- `Testing`: `FixedProvider` (every contract, in memory), `RecordedHttpClient`, `BareTestCase`.
- **Providers** are packages: [omnistay/amadeus](https://github.com/glitchr-omnisense/omnistay-amadeus)
  (hotels), [omnistay/local](https://github.com/glitchr-omnisense/omnistay-local) (a site's own
  listings).

## Usage policies

Each provider declares its usage policy as data (its `policy.json`: rate limits, rights,
attribution, requirements, where and when it was read), and every call through `search()`,
`availability()`, `calendar()`, `book()`, `cancel()` or `content()` - and through `searchAll()`,
`availabilityOf()`, `propertyOf()`, `bookOffer()` - is held to it: checked before, counted after,
refused with `UsageLimitReachedException` once a limit is reached or while the provider asks to
wait (a 429 and its `Retry-After`, a `RateLimit` field with nothing left). In `searchAll()`, a
provider over its limit is one of the failures; the others' answers stand. A site may tighten a
policy in its configuration, never loosen it:

```php
'homes' => [
    'factory' => 'local',
    'policy' => ['rateLimits' => [['max' => 60, 'window' => 'minute', 'operations' => ['book']]]],
    'usage' => ['commercial' => true],
],
```

`$registry->policy('homes')` gives the policy as the site runs it. Pass a shared
`RateLimiterInterface` as the registry's third argument when the application runs in many
processes. See [docs/policies.md](docs/policies.md).

The core needs no framework: it requires `omnisense/contracts` (the kernel every omnisense family
builds on: provider, factory, registry, JSON client, usage policies, testing kit) and
`symfony/http-client-contracts`, nothing else.

Manual: [docs/index.md](docs/index.md). License: MIT.
