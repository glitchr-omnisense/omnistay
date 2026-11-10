# Changelog

## 1.0.0 (unreleased)

- Writes sent once: `Http\JsonClient` keeps the HTTP client it is given with its retries off
  (omnisense/contracts' `Http\NoRetry`, which omnisense/contracts 1568f2c brings; with an older
  kernel the client is kept as given), so Symfony's `RetryableHttpClient` (`framework.http_client`'s
  `retry_failed`) never sends a booking or a cancellation twice, and a 429 reaches the provider's
  Backoff. A test client is kept as given. docs/policies.md, docs/symfony.md.

- 2026-11-10: on omnisense/contracts, the kernel every family builds on. Config, ProviderInterface,
  ProviderFactoryInterface, ProviderFactory (with "omnistay.name" and name() as before), Registry,
  Http\JsonClient, Data, Redaction, Testing\RecordedHttpClient, RecordedResponse and BareTestCase
  keep their names and their API, each a thin subclass of the kernel's (`bin/load-all.php` moved
  there). Usage policies: `Capabilities::policy()` (given, or made of `$keyless`, `$attribution`
  and `$terms`, now deprecated; `$sandbox` stays), every call through `search()`, `availability()`,
  `calendar()`, `book()`, `cancel()`, `content()` guarded (Policy\Guarded*), and through them
  `searchAll()` (a provider over its limit among the failures), `availabilityOf()`, `propertyOf()`,
  `bookOffer()`; a site's configuration tightening a policy (`policy`, `usage`),
  `Registry::policy()`, `policies()`, `guard()`, a shared limiter as the registry's third
  argument, Exception\UsageLimitReachedException. The harness installs omnisense/contracts from
  its checkout (OMNISENSE_CONTRACTS) and runs its tests too. docs/policies.md.
- The family: models (Property, Location, Amenity, Photo, RoomType, Stay, Guests, StayQuery,
  SearchResult, SearchResults, Offer, Rate, Charge, NightRate, CancellationPolicy, Board,
  PaymentTiming, Availability, CalendarDay, Traveller, PaymentCard, BookingRequest, Booking,
  BookingStatus, Money, CarHire, Package), contracts by capability (search, availability,
  calendar, book, cancel, content), Registry with searchAll(), Config, ProviderFactory,
  Capabilities, Testing (FixedProvider, RecordedHttpClient, BareTestCase), the Docker harness.
- Providers: omnistay/amadeus, omnistay/local.
