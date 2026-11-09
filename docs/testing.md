# Testing

- `Omnistay\Testing\FixedProvider` (factory `fixed`) answers all six contracts from the properties
  it is given, their nightly prices and blocked nights; one factory serves several names (each
  provider holds the properties whose ref names it). Offers' ids are
  `<id>|<check-in>|<check-out>|<adults>`, bookings' references `FX-1`, `FX-2`...
- `Omnistay\Local\InMemoryStore` is a store for omnistay/local in memory.
- `RecordedHttpClient` answers by route and keeps every request; providers' own tests use
  Symfony's `MockHttpClient` to record the exact requests (method, address with its query, body,
  token).
- `BareTestCase`: each package checks that its classes load with what it requires alone.

Nothing in the family's tests calls a network.
