# Models

Every model is a `readonly` value object with a `toArray()`.

| Model | What it holds |
|---|---|
| `Money` | an amount in minor units and an ISO 4217 currency; `fromDecimal()`, `decimal()`, `add()`, `percent()`; JPY has no minor unit, KWD three |
| `Stay` | check-in and check-out dates (midnight UTC); `count()` nights, `dates()` each night, `overlaps()` (a check-out on the other's check-in shares no night), `contains()` |
| `Guests` | adults (one at least), children by age, pets; `count()` everyone |
| `Location` | latitude, longitude, address lines, city, postal code, country, IATA city code, area; `exact: false` for a home whose door stays hidden until booked; `distanceTo()` |
| `Property` | `PropertyRef`, name, `PropertyKind`, `Location`, description, `Amenity`s and the provider's other codes, `Photo`s (with their credit), `RoomType`s, stars, guests' rating and count, host, check-in and check-out times, capacity, chain |
| `PropertyRef` | the provider's name in the registry and its id there: `"hotels:ARMADAIT"` |
| `StayQuery` | a stay, guests, a place (text, city code or point and radius), kinds, amenities, a nightly price range, a minimum rating, currency, language, rooms, page; `accepts()` applies the filters to a result |
| `SearchResult` | a property, the stay's lowest total there, nights, available, the cheapest offer, distance; `nightly()` |
| `SearchResults` | several providers' results merged, and the providers that failed |
| `Offer` | id, property, stay, guests, `Rate`, `CancellationPolicy`, `RoomType`, `Board`, `PaymentTiming`, rooms, expiry, rate code, instant or on request |
| `Rate` | the total, `Charge`s (`ChargeKind`: nights, cleaning, service, tax, discount, other; `included` for a tax inside the price), `NightRate`s; `addsUp()`, `base()` |
| `CancellationPolicy` | refundable, free until, penalty (amount or percent), the provider's text; `isFreeAt()`, `penaltyAt()` |
| `Availability` | whether a property takes the stay, its offers, the reason when not (booked, blocked, min_stay, capacity...), the minimum stay; `cheapest()` |
| `CalendarDay` | a night: free or not and why, price, minimum stay from it, check-in allowed |
| `Traveller`, `PaymentCard` | a guest; a card a provider takes itself (its number never printed: `last4()`) |
| `BookingRequest` | the offer, travellers, a card or the reference of a payment the site took, the site's reference, a message, an idempotency key |
| `Booking`, `BookingStatus` | reference, status (pending, confirmed, on request, cancelled, refused, completed; `holds()`), property, stay, guests, total, confirmation numbers, the provider's raw answer |
| `CarHire`, `Package` | a car (ACRISS category, pick-up and return, rate) and a package (stays, cars, extras at one price; `partsTotal()`), for the providers that sell them |
