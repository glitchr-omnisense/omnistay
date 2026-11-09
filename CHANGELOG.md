# Changelog

## 1.0.0 (unreleased)

- The family: models (Property, Location, Amenity, Photo, RoomType, Stay, Guests, StayQuery,
  SearchResult, SearchResults, Offer, Rate, Charge, NightRate, CancellationPolicy, Board,
  PaymentTiming, Availability, CalendarDay, Traveller, PaymentCard, BookingRequest, Booking,
  BookingStatus, Money, CarHire, Package), contracts by capability (search, availability,
  calendar, book, cancel, content), Registry with searchAll(), Config, ProviderFactory,
  Capabilities, Testing (FixedProvider, RecordedHttpClient, BareTestCase), the Docker harness.
- Providers: omnistay/amadeus, omnistay/local.
