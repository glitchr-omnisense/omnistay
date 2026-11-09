# Writing a provider

A provider is a package `omnistay/<name>` with a factory and a provider class:

- the factory extends `ProviderFactory`: `populate()` sets `omnistay.factory_name`,
  `omnistay.factory_title`, `omnistay.required_options` and the defaults; `build()` returns the
  provider, given `self::name($c)` (its name in the registry, which its `PropertyRef`s carry). A
  provider that calls an API takes an `HttpClientInterface` in the factory's constructor and wraps
  it in `Http\JsonClient`;
- the provider implements `ProviderInterface` and the contracts it can fulfil, and says so in
  `capabilities()`; it maps its codes to the family's (`Amenity`, `Board`, `BookingStatus`) and
  keeps the others (`Property::$otherAmenities`, `Booking::$raw`);
- its tests answer from fixtures written after the provider's reference, through `MockHttpClient`,
  and assert the exact requests; a `BareTest` extends `Testing\BareTestCase`.

Add it to `docker/harness/plugins.php`. Candidates named when the family was reserved: booking,
expedia (partner programmes, not public APIs today), car-hire suppliers.
