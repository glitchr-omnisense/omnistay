# Symfony

There is no bundle to install: the registry is a service of the application's.

```yaml
# config/services.yaml
services:
    Omnistay\Amadeus\AmadeusFactory: ~                       # takes the http_client service
    Omnistay\Local\LocalFactory:
        arguments: ['@App\Stay\ListingStore']               # your ListingStoreInterface
    Omnistay\Registry:
        arguments:
            - ['@Omnistay\Amadeus\AmadeusFactory', '@Omnistay\Local\LocalFactory']
            - hotels: { factory: amadeus, options: { client_id: '%env(AMADEUS_CLIENT_ID)%', client_secret: '%env(AMADEUS_CLIENT_SECRET)%' } }
              homes: { factory: local, options: { service_fee: 12 } }
```

The `http_client` service is kept with its retries off (omnisense/contracts' `Http\NoRetry`, in
`Http\JsonClient`): `retry_failed` never sends a booking twice.

Credentials go by reference (`%env()%`, the secrets vault), never in the file. On an omnibase
site, `omnibase/booking` registers the registry itself from its `booking.providers` configuration
and gives `local` its Doctrine store.

Laravel: the same registry, bound as a singleton in a service provider.
