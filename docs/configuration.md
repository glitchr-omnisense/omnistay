# Configuration

```php
new Registry($factories, [
    '<name>' => ['factory' => '<factory name>', 'options' => [...]],
]);
```

`<name>` is the provider's name in the application; every `PropertyRef` it gives carries it. The
registry passes it to the factory as the option `omnistay.name`.

## amadeus

| Option | Default | |
|---|---|---|
| `client_id`, `client_secret` | required | an Amadeus application's API key and secret, by reference |
| `environment` | `test` | `test` (https://test.api.amadeus.com: nothing booked is real) or `production` |
| `host` | - | another address for the same APIs |
| `currency`, `language` | - | the offers' currency and texts' language when the query gives none |
| `hotel_source` | `ALL` | `BEDBANK`, `DIRECTCHAIN` or `ALL` |
| `agency_email` | - | the travel agent's contact a booking carries |

## local

| Option | Default | |
|---|---|---|
| `title` | `Homes` | |
| `service_fee` | `0` | the site's fee charged to the guest, in percent of what the host charges |
| `hold_minutes` | `30` | how long a pending booking holds its nights while the guest pays |

The store is given to `LocalFactory`'s constructor, with an optional clock.

## fixed (Testing)

No option: `FixedProviderFactory` is given its properties, their nightly prices and blocked nights.
