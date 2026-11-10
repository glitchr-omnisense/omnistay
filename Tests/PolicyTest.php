<?php

namespace Omnistay\Tests;

use Omnisense\Contracts\Policy\Caching;
use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\RateLimit;
use Omnisense\Contracts\Policy\UsageNotPermitted;
use Omnisense\Contracts\Policy\UsagePolicy;
use Omnistay\Capabilities;
use Omnistay\Config;
use Omnistay\Exception\OmnistayException;
use Omnistay\Exception\UsageLimitReachedException;
use Omnistay\Http\JsonClient;
use Omnistay\Model\Guests;
use Omnistay\Model\Location;
use Omnistay\Model\Money;
use Omnistay\Model\Page;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyKind;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Stay;
use Omnistay\Model\StayQuery;
use Omnistay\Policy\GuardedAvailability;
use Omnistay\Policy\GuardedSearch;
use Omnistay\ProviderFactory;
use Omnistay\ProviderInterface;
use Omnistay\Registry;
use Omnistay\SearchInterface;
use Omnistay\Testing\FixedProvider;
use Omnistay\Testing\FixedProviderFactory;
use Omnistay\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Every call through a contract is guarded by the provider's usage policy, as the site tightened it. */
final class PolicyTest extends TestCase
{
    public function testCallsThroughAContractCountUnderTheSitesLimit(): void
    {
        $registry = self::homes(['rateLimits' => [['max' => 2, 'window' => 'minute', 'operations' => ['search', 'availability']]]]);
        $query = new StayQuery(Stay::of('2026-12-18', '2026-12-21'), place: 'Annecy');

        self::assertInstanceOf(GuardedSearch::class, $registry->search('homes'));
        self::assertInstanceOf(FixedProvider::class, $registry->search('homes')->inner());
        self::assertInstanceOf(FixedProvider::class, $registry->get('homes'), 'get() gives the provider itself');
        self::assertSame('homes', $registry->search('homes')->getName());
        $registry->search('homes')->search($query);
        self::assertInstanceOf(GuardedAvailability::class, $registry->availability('homes'));
        $registry->availabilityOf(new PropertyRef('homes', 'L-1'), Stay::of('2026-12-18', '2026-12-21'), Guests::adults(2));
        $registry->propertyOf(new PropertyRef('homes', 'L-1'));
        try {
            $registry->availabilityOf(new PropertyRef('homes', 'L-1'), Stay::of('2026-12-18', '2026-12-21'), Guests::adults(2));
            self::fail('A third counted call went through.');
        } catch (OmnistayException $e) {
            self::assertInstanceOf(UsageLimitReachedException::class, $e);
            self::assertSame('homes\'s usage policy allows 2 a minute for availability, search: reached, try again in 60 s.', $e->getMessage());
        }
        self::assertSame(0, $registry->guard('homes')->remaining('search'));
        self::assertNull($registry->guard('homes')->remaining('property'), 'descriptions are not counted');

        $results = $registry->searchAll($query);
        self::assertSame([], $results->items);
        self::assertStringContainsString('usage policy allows 2 a minute', $results->failures['homes'] ?? '', 'searchAll names the provider whose limit is reached among its failures');
    }

    public function testA429AndItsRetryAfterHoldEveryCallUntilTheWaitIsOver(): void
    {
        $http = RecordedHttpClient::routes(['~/search~' => [429, '{"error":"too many requests"}', ['retry-after' => ['120']]]]);
        $registry = new Registry([self::overHttp($http)], ['hotels' => ['factory' => 'http']]);

        try {
            $registry->search('hotels')->search(new StayQuery(place: 'Annecy'));
            self::fail('A 429 was read as stays.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(120, $e->retryAfter);
            self::assertSame('http answered 429 Too Many Requests: try again in 120 s.', $e->getMessage());
        }
        try {
            $registry->search('hotels')->search(new StayQuery(place: 'Nice'));
            self::fail('A call went out while the provider asked to wait.');
        } catch (UsageLimitReachedException $e) {
            self::assertGreaterThanOrEqual(119, $e->retryAfter);
        }
        self::assertCount(1, $http->requests, 'the wait held the next request back');
    }

    public function testASiteMayTightenAPolicyNeverLoosenIt(): void
    {
        $declared = UsagePolicy::builder('http')->limit(RateLimit::perDay(1000))->commercial(Permission::Forbidden)->build();
        $tighter = new Registry([self::overHttp(RecordedHttpClient::routes([]), $declared)], ['hotels' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 100, 'window' => 'day']], 'rights' => ['caching' => ['permission' => 'forbidden']]]]]);

        self::assertSame(100, $tighter->guard('hotels')->remaining('search'));
        self::assertSame(Permission::Forbidden, $tighter->policy('hotels')->rights->caching->permission);

        $looser = new Registry([self::overHttp(RecordedHttpClient::routes([]), $declared)], ['hotels' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 5000, 'window' => 'day']]]]]);
        try {
            $looser->search('hotels');
            self::fail('A higher limit than the provider\'s was accepted.');
        } catch (InvalidPolicyException $e) {
            self::assertStringContainsString('5000 a day', $e->getMessage());
        }

        $commercial = new Registry([self::overHttp(RecordedHttpClient::routes([]), $declared)], ['hotels' => ['factory' => 'http', 'usage' => ['commercial' => true]]]);
        $this->expectException(UsageNotPermitted::class);
        $commercial->search('hotels');
    }

    public function testCapabilitiesWrittenTheOlderWayGiveAPolicy(): void
    {
        $capabilities = new Capabilities(search: true, sandbox: true, attribution: 'Data from Example', terms: ['https://example.org/terms'], keyless: true);
        $policy = $capabilities->policy();

        self::assertSame(UsagePolicy::UNNAMED, $policy->provider);
        self::assertSame('Data from Example', $policy->rights->attribution->text);
        self::assertFalse($policy->requirements->key);
        self::assertNull($policy->rateLimits);
        self::assertSame('https://example.org/terms', $policy->provenance->terms);
        self::assertSame($policy->toArray(), $capabilities->toArray()['policy']);
        self::assertTrue($capabilities->sandbox, 'the sandbox stays omnistay\'s own');
        $registry = self::homes();
        self::assertSame('homes', $registry->policy('homes')->provider, 'the registry names it after its provider');
        self::assertSame(['homes' => $registry->policy('homes')], $registry->policies());
    }

    public function testTheOlderFieldsAreReadFromAPolicy(): void
    {
        $policy = UsagePolicy::builder('x')->limit(RateLimit::perDay(10))->attribution('X data', 'https://x.example')
            ->caching(Caching::upTo(60))->commercial(Permission::Allowed)->provenance(terms: 'https://x.example/terms', policy: 'https://x.example/api')->build();
        $capabilities = new Capabilities(search: true, policy: $policy);

        self::assertSame($policy, $capabilities->policy());
        self::assertSame('X data', $capabilities->attribution);
        self::assertSame(['https://x.example/terms', 'https://x.example/api'], $capabilities->terms);
        self::assertTrue($capabilities->keyless);
        self::assertSame('Mine', (new Capabilities(attribution: 'Mine', policy: $policy))->attribution, 'a field given stays as given');
    }

    public function testAFactoryOutsideARegistryNamesItsProviderAfterItself(): void
    {
        self::assertSame('fixed', (new FixedProviderFactory())->create()->getName());
        self::assertSame('homes', (new FixedProviderFactory())->create(['omnistay.name' => 'homes'])->getName());
    }

    /** @param array<string, mixed> $policy */
    private static function homes(array $policy = []): Registry
    {
        $fixed = new FixedProviderFactory([new Property(new PropertyRef('homes', 'L-1'), 'Cabane', PropertyKind::Cabin, new Location(45.9, 6.13, city: 'Annecy', countryCode: 'FR'))], ['L-1' => Money::of(9500, 'EUR')]);

        return new Registry([$fixed], ['homes' => ['factory' => 'fixed'] + ([] === $policy ? [] : ['policy' => $policy])]);
    }

    /** A provider over HTTP, its JsonClient given the provider's Backoff: a search is one GET /search. */
    private static function overHttp(HttpClientInterface $http, ?UsagePolicy $policy = null): ProviderFactory
    {
        return new class($http, $policy) extends ProviderFactory {
            public function __construct(private readonly HttpClientInterface $http, private readonly ?UsagePolicy $policy)
            {
            }

            protected function populate(Config $c): void
            {
                $c->defaults(['omnistay.factory_name' => 'http', 'omnistay.required_options' => []]);
            }

            protected function build(Config $c): ProviderInterface
            {
                $json = new JsonClient($this->http, 'Example', [], $c->backoff());

                return new class($json, $this->policy) implements ProviderInterface, SearchInterface {
                    public function __construct(private readonly JsonClient $json, private readonly ?UsagePolicy $policy)
                    {
                    }

                    public function getName(): string
                    {
                        return 'http';
                    }

                    public function getTitle(): string
                    {
                        return 'Over HTTP';
                    }

                    public function capabilities(): Capabilities
                    {
                        return new Capabilities(search: true, searchBy: ['place'], policy: $this->policy);
                    }

                    public function search(StayQuery $query): Page
                    {
                        $this->json->get('https://api.example.org/search', ['place' => $query->place]);

                        return new Page([]);
                    }
                };
            }
        };
    }
}
