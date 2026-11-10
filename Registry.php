<?php

namespace Omnistay;

use Omnisense\Contracts\Policy\RateLimiterInterface;
use Omnisense\Contracts\Provider\ProviderInterface as BaseProvider;
use Omnisense\Contracts\Provider\Registry as BaseRegistry;
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
use Omnistay\Policy\GuardedAvailability;
use Omnistay\Policy\GuardedBook;
use Omnistay\Policy\GuardedCalendar;
use Omnistay\Policy\GuardedCancel;
use Omnistay\Policy\GuardedContent;
use Omnistay\Policy\GuardedSearch;

/**
 * The application's providers by name, each built once from its factory and
 * options, each call through a contract guarded by the provider's usage
 * policy:
 *
 *   $registry = new Registry([new AmadeusFactory($http), new LocalFactory($store)], [
 *       'hotels' => ['factory' => 'amadeus', 'options' => ['client_id' => '...', 'client_secret' => '...']],
 *       'homes' => ['factory' => 'local'],
 *   ], $limiter);
 *
 *   $registry->searchAll(new StayQuery(Stay::of('2026-12-18', '2026-12-21'), place: 'Annecy'));  // hotels and homes, one list
 *   $registry->availabilityOf($result->property->ref, $stay, $guests);                          // asked to the provider it came from
 *   $registry->bookOffer($request);                                                             // booked there
 *
 * A PropertyRef carries the name of the provider that gave it, so a
 * question about a property goes back to that provider.
 *
 * search(), availability(), calendar(), book(), cancel() and content() give
 * the provider behind its guard (Policy\Guarded*): each call is checked
 * against the policy's rate limits for its operation - the contract's
 * method name, "search", "availability", "offer", "calendar", "book",
 * "booking", "cancel", "property", "properties" - then counted, and refused
 * with Exception\UsageLimitReachedException (an OmnistayException: in
 * searchAll(), that provider's failure) once a limit is reached or while
 * the provider asks to wait; searchAll(), availabilityOf(), propertyOf()
 * and bookOffer() ask through them too. get() gives the provider itself,
 * unguarded. "policy" tightens the provider's policy, never loosens it;
 * "usage" says what the site is (omnisense/contracts' Registry).
 */
final class Registry extends BaseRegistry
{
    /** @var string */
    public const PREFIX = Config::PREFIX;

    /** @var array<string, ProviderInterface> the guarded contracts, by "contract name" */
    private array $guarded = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                                                                         $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>, policy?: array<string, mixed>, usage?: array<string, mixed>}> $config
     * @param ?RateLimiterInterface                                                                                                      $limiter   where every provider's calls are counted: an in-memory one by default, a shared one for an application served by many processes
     */
    public function __construct(iterable $factories, array $config, ?RateLimiterInterface $limiter = null)
    {
        parent::__construct($factories, $config, $limiter);
    }

    public function get(string $name): ProviderInterface
    {
        $provider = parent::get($name);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" provider (%s) is no omnistay provider.', $name, $provider::class));
    }

    public function search(string $name): SearchInterface
    {
        $guarded = $this->guarded['search '.$name] ??= new GuardedSearch($this->provider($name, SearchInterface::class, 'search'), $this->guard($name));

        return $guarded instanceof SearchInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function availability(string $name): AvailabilityInterface
    {
        $guarded = $this->guarded['availability '.$name] ??= new GuardedAvailability($this->provider($name, AvailabilityInterface::class, 'give availability'), $this->guard($name));

        return $guarded instanceof AvailabilityInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function calendar(string $name): CalendarInterface
    {
        $guarded = $this->guarded['calendar '.$name] ??= new GuardedCalendar($this->provider($name, CalendarInterface::class, 'give a calendar'), $this->guard($name));

        return $guarded instanceof CalendarInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function book(string $name): BookInterface
    {
        $guarded = $this->guarded['book '.$name] ??= new GuardedBook($this->provider($name, BookInterface::class, 'book'), $this->guard($name));

        return $guarded instanceof BookInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function cancel(string $name): CancelInterface
    {
        $guarded = $this->guarded['cancel '.$name] ??= new GuardedCancel($this->provider($name, CancelInterface::class, 'cancel'), $this->guard($name));

        return $guarded instanceof CancelInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    public function content(string $name): ContentInterface
    {
        $guarded = $this->guarded['content '.$name] ??= new GuardedContent($this->provider($name, ContentInterface::class, 'describe properties'), $this->guard($name));

        return $guarded instanceof ContentInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    /**
     * One query asked to every provider that can read its place (or those
     * named), the answers merged: filtered by the query (StayQuery::accepts()),
     * the available first, then the cheapest per night, then by distance.
     * Each provider is asked through its guard: one that fails, or whose
     * usage limit is reached, is named in the failures; the others' answers
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
                foreach ($this->search($name)->search($query)->items as $result) {
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

    /**
     * The configured providers' names, in the configuration's order; those
     * keeping a contract ("search", "availability", "book"...) when one is
     * named.
     *
     * @return list<string>
     */
    public function names(?string $contract = null): array
    {
        return parent::names($contract);
    }

    /**
     * A provider that keeps a contract, or NotSupportedException.
     *
     * @template T of object
     *
     * @param class-string<T> $contract
     *
     * @return T&ProviderInterface
     */
    private function provider(string $name, string $contract, string $capability): object
    {
        $provider = $this->contract($name, $contract, $capability);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" provider (%s) is no omnistay provider.', $name, $provider::class));
    }

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }

    protected function notSupported(BaseProvider $provider, string $name, string $capability): \Throwable
    {
        return NotSupportedException::capability($provider, $name, $capability);
    }
}
