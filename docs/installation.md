# Installation

```sh
composer require omnisense/omnistay omnistay/local     # a site's own listings
composer require omnistay/amadeus                     # hotels through Amadeus (symfony/http-client comes with it)
```

PHP 8.2 or later. The core requires `symfony/http-client-contracts` (interfaces only); a provider
that calls an API requires `symfony/http-client` and accepts a client passed from outside - the
application's, or a `MockHttpClient` in a test.

## In plain PHP

```php
use Omnistay\Local\{InMemoryStore, LocalFactory};
use Omnistay\Model\{Guests, Stay, StayQuery};
use Omnistay\Registry;

$registry = new Registry([new LocalFactory(new InMemoryStore($listings))], [
    'homes' => ['factory' => 'local', 'options' => ['service_fee' => 12]],
]);
$page = $registry->search('homes')->search(new StayQuery(Stay::of('2026-12-18', '2026-12-21'), new Guests(2), place: 'Annecy'));
```

No container, no configuration file: a registry is built by hand, its factories given what they
need (an HTTP client, a store). The family's `BareTest` checks that nothing of a framework loads
on that path.

A site keeps its listings in its own database: it implements `Omnistay\Local\ListingStoreInterface`
(five methods; omnibase/booking's `DoctrineListingStore` is one) and gives it to `LocalFactory`.

Next: [Symfony](symfony.md), [Configuration](configuration.md).
