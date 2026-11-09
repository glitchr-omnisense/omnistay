# omnisense/omnistay

Omnistay is one PHP family for stays: where to sleep, what it costs for these dates and these
guests, and booking it - whoever sells it. A hotel from a distribution system and a home rented by
its owner are both a `Property`; both answer the same questions:

| Contract | The question | `Capabilities` flag |
|---|---|---|
| `SearchInterface::search(StayQuery)` | what is there around this place, for these dates, and for how much | `search` |
| `AvailabilityInterface::availability()` | does this property take us on these dates, and its offers | `availability` |
| `AvailabilityInterface::offer($id)` | this offer as it stands now, re-priced | `availability` |
| `CalendarInterface::calendar()` | the property's nights one by one: free, price, minimum stay | `calendar` |
| `BookInterface::book()` / `booking()` | book this offer; a booking by its reference | `book` |
| `CancelInterface::cancel()` | cancel it | `cancel` |
| `ContentInterface::property()` / `properties()` | the property's whole description | `content` |

```php
$registry->searchAll($query);                               // every provider that reads the place, merged
$registry->search('hotels')->search($query);                // one provider, one page
$registry->availabilityOf($ref, $stay, $guests);            // asked to the provider the ref names
$registry->availability('hotels')->offer($offerId);         // re-priced before booking
$registry->calendar('homes')->calendar($ref, $from, $to);   // CalendarDay[]
$registry->bookOffer($request);                             // Booking
$registry->cancel('homes')->cancel($booking->reference);    // Booking, cancelled
```

The design is the one of the other Omnisense families: a provider's **factory** fills a
configuration with its name, its required options and the defaults of the others, then builds the
**provider**; a **registry** holds the application's providers by name. A provider says what it
can be asked in its **capabilities** - its contracts, how its search reads a place (a text, an IATA
city code, a point), the kinds of property it holds, whether its search prices the stay, whether
it takes a card, whether it runs on a test environment - and a question it cannot answer is
refused before any call (`NotSupportedException`).

## When to use it

- A booking site that sells hotel rooms and private rentals side by side (solstay is the first).
- A travel agency's site (omnibase/booking) that books the stays of a trip.
- A host's own site that takes bookings for its home without a portal.

It stores nothing: keeping a booking, its payment and its messages is the application's job
(omnibase/booking does it for a Symfony site). It takes no payment either: a provider that charges
the guest itself (a hotel through Amadeus) is given a card; a site that charges the guest through
omnitrade gives the payment's reference instead.

## Providers

| Package | Factory | Contracts | Searches by | Holds | Credentials |
|---|---|---|---|---|---|
| [`omnistay/amadeus`](https://github.com/glitchr-omnisense/omnistay-amadeus) | `amadeus` | search, availability, book, content | city code, point | hotels | a client id and secret |
| [`omnistay/local`](https://github.com/glitchr-omnisense/omnistay-local) | `local` | all six | text, city code, point | homes, rooms, cabins... | none (the site's store) |

`Omnistay\Testing\FixedProvider` answers all six from properties it holds ([Testing](testing.md)).

## This manual

- [Installation](installation.md): plain PHP first, then [Symfony](symfony.md).
- [Models](models.md): every model and enum.
- [Searching, pricing and booking](usage.md).
- [Configuration](configuration.md): the registry's configuration and every provider's options.
- [Errors](errors.md).
- [Testing](testing.md) and [the Docker harness](harness.md).
- [Writing a provider](providers.md).
- [Verified, and not](verified.md).
