# Usage policies

Every omnistay provider carries a usage policy: omnisense/contracts' `UsagePolicy`
([its schema and rules](https://github.com/glitchr-omnisense/contracts/blob/1.x/docs/policies.md)).

| Provider | Declared | Rate limits | Rights |
|---|---|---|---|
| omnistay/amadeus | its `policy.json`, read on 2026-11-10 (Amadeus' archived developer guides: the Self-Service terms are gone) | 40 a second per key in production, 10 on the test environment | commercial use on contract; caching, storage, redistribution and credit unknown; a key |
| omnistay/local | its `policy.json`: the site's own data, no third party's terms | none | every right; no key |

## Where calls are checked

`Registry::search()`, `availability()`, `calendar()`, `book()`, `cancel()` and `content()` give the
provider behind its guard (`Omnistay\Policy\GuardedSearch`...). Each call is one operation,
named after its method: `search`, `availability`, `offer`, `calendar`, `book`, `booking`, `cancel`,
`property`, `properties`. A limit with `operations` counts only those; one without counts every
call. `searchAll()`, `availabilityOf()`, `propertyOf()` and `bookOffer()` ask through the guarded
contracts; in `searchAll()` a provider whose limit is reached is named among the failures, the
others' answers stand. `Registry::get()` gives the provider itself, unguarded.

A call over a limit is never made: `UsageLimitReachedException` (an `OmnistayException`), with
`retryAfter` in seconds and the `limit` reached. A provider's own refusal - a 429, a 503 with
`Retry-After`, a `RateLimit: "...";r=0;t=30` field - holds every following call of that provider
until the wait is over.

A limit counts the family's calls, not the provider's HTTP requests: an Amadeus search is a Hotel
List request and a Hotel Search request per twenty hotels, and the access token one more.

## A site's configuration

```php
$stays = new Registry($factories, [
    'hotels' => [
        'factory' => 'amadeus',
        'options' => ['client_id' => '...', 'client_secret' => '...', 'environment' => 'production'],
        'policy' => ['rateLimits' => [['max' => 20, 'window' => 'second', 'scope' => 'key']]],
        'usage' => ['commercial' => true, 'contract' => true],
    ],
], $limiter);
```

- `policy` tightens the provider's: lower limits are added, stricter rights replace the declared
  ones. A looser value (100 a second where Amadeus says 40) is refused with
  `InvalidPolicyException` when the provider is first used.
- `usage` says what the site is: a commercial site on a provider whose terms ask a contract is
  refused at once (`UsageNotPermitted`) unless it says it holds one (`contract`).
- Both may sit in `options` too.
- `$limiter` (`RateLimiterInterface`): an in-memory one by default, which counts within one PHP
  process. A web application gives a shared one (omnibridge/symfony's, over symfony/rate-limiter
  and a cache pool), or the counts start over with every request.

`$stays->policy('hotels')` and `$stays->policies()` give the policies as the site runs them.

## Older Capabilities

`Capabilities` keeps `$keyless`, `$attribution` and `$terms` (deprecated). A provider that gives
them alone gets a policy made of them, its limits unknown (`UsagePolicy::described()`). A provider
that gives a `policy:` gets them read from it. `$sandbox` stays omnistay's own.
