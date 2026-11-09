# omnisense/omnistay

Where to stay and what to book for a trip, under any PHP host, whoever sells it. Hotels from a
distribution system and homes rented by their owners answer the same contracts, so a site searches
both through one interface, prices a stay line by line, books it and cancels it.

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

The core needs no framework: it requires `symfony/http-client-contracts` and nothing else.

Manual: [docs/index.md](docs/index.md). License: MIT.
