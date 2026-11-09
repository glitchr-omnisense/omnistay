<?php

namespace Omnistay;

use Omnistay\Exception\InvalidConfigException;
use Omnistay\Exception\NotSupportedException;
use Omnistay\Exception\OmnistayException;
use Omnistay\Model\Availability;
use Omnistay\Model\Booking;
use Omnistay\Model\BookingRequest;
use Omnistay\Model\Guests;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\SearchResult;
use Omnistay\Model\SearchResults;
use Omnistay\Model\Stay;
use Omnistay\Model\StayQuery;

/**
 * The application's providers by name, each built once from its factory and
 * options:
 *
 *   $registry = new Registry([new AmadeusFactory($http), new LocalFactory($store)], [
 *       'hotels' => ['factory' => 'amadeus', 'options' => ['client_id' => '...', 'client_secret' => '...']],
 *       'homes' => ['factory' => 'local'],
 *   ]);
 *
 *   $registry->searchAll(new StayQuery(Stay::of('2026-12-18', '2026-12-21'), place: 'Annecy'));  // hotels and homes, one list
 *   $registry->availabilityOf($result->property->ref, $stay, $guests);                          // asked to the provider it came from
 *   $registry->bookOffer($request);                                                             // booked there
 *
 * A PropertyRef carries the name of the provider that gave it, so a
 * question about a property goes back to that provider.
 */
final class Registry
{
    /** @var array<string, ProviderFactoryInterface> */
    private array $factories = [];

    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                     $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): ProviderInterface
    {
        return $this->providers[$name] ??= $this->factory($name)->create(['omnistay.name' => $name] + ($this->config[$name]['options'] ?? []));
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    public function search(string $name): SearchInterface
    {
        $provider = $this->get($name);

        return $provider instanceof SearchInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'search');
    }

    public function availability(string $name): AvailabilityInterface
    {
        $provider = $this->get($name);

        return $provider instanceof AvailabilityInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'give availability');
    }

    public function calendar(string $name): CalendarInterface
    {
        $provider = $this->get($name);

        return $provider instanceof CalendarInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'give a calendar');
    }

    public function book(string $name): BookInterface
    {
        $provider = $this->get($name);

        return $provider instanceof BookInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'book');
    }

    public function cancel(string $name): CancelInterface
    {
        $provider = $this->get($name);

        return $provider instanceof CancelInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'cancel');
    }

    public function content(string $name): ContentInterface
    {
        $provider = $this->get($name);

        return $provider instanceof ContentInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'describe properties');
    }

    /**
     * One query asked to every provider that can read its place (or those
     * named), the answers merged: filtered by the query (StayQuery::accepts()),
     * the available first, then the cheapest per night, then by distance.
     * A provider that fails is named in the failures; the others' answers
     * stand.
     */
    public function searchAll(StayQuery $query, string ...$names): SearchResults
    {
        $items = [];
        $failures = [];
        $asked = [];
        foreach ($names ?: $this->names() as $name) {
            $provider = $this->get($name);
            if (!$provider instanceof SearchInterface || !$provider->capabilities()->reads($query)) {
                continue;
            }
            $asked[] = $name;
            try {
                foreach ($provider->search($query)->items as $result) {
                    if ($query->accepts($result)) {
                        $items[] = $result;
                    }
                }
            } catch (OmnistayException $e) {
                $failures[$name] = $e->getMessage();
            }
        }
        usort($items, static function (SearchResult $a, SearchResult $b): int {
            $an = $a->nightly();
            $bn = $b->nightly();

            return [!$a->available, null === $an, $an->amount ?? 0, $a->distance ?? INF] <=> [!$b->available, null === $bn, $bn->amount ?? 0, $b->distance ?? INF];
        });

        return new SearchResults(\array_slice($items, 0, $query->limit * max(1, \count($asked))), $failures, $asked);
    }

    /** The property's availability, asked to the provider it came from. */
    public function availabilityOf(PropertyRef $property, Stay $stay, Guests $guests, ?string $currency = null): Availability
    {
        return $this->availability($property->provider)->availability($property, $stay, $guests, $currency);
    }

    /** The property's description, from the provider it came from. */
    public function propertyOf(PropertyRef $property, ?string $language = null): ?Property
    {
        return $this->content($property->provider)->property($property, $language);
    }

    /** The request's offer booked at the provider it came from. */
    public function bookOffer(BookingRequest $request): Booking
    {
        return $this->book($request->offer->property->provider)->book($request);
    }

    /** @return array<string, mixed> the options a provider is configured with */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /** @return list<string> the configured providers' names */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $name): ProviderFactoryInterface
    {
        $provider = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" provider; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$provider['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" provider; installed: %s.', $provider['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }
}
