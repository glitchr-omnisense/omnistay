<?php

namespace Omnistay;

use Omnisense\Contracts\Policy\UsagePolicy;
use Omnisense\Contracts\Provider\CapabilitiesInterface;
use Omnistay\Model\PropertyKind;
use Omnistay\Model\StayQuery;

/**
 * What a provider is, said before it is asked anything: the contracts it
 * answers, how it reads a place, the kinds of property it holds, whether
 * its search prices the stay, and its usage policy: its rate limits and
 * what its terms ask of whoever shows its records.
 *
 * The policy is omnisense/contracts' UsagePolicy. The three fields written
 * before it ($keyless, $attribution, $terms) stay, read from the policy
 * when it is given and they are not: a provider that gives them alone gets
 * a policy made of them, its limits unknown (UsagePolicy::described()).
 * $sandbox stays omnistay's own.
 */
final readonly class Capabilities implements CapabilitiesInterface
{
    /** @deprecated since 1.x, read policy()->requirements->key */
    public bool $keyless;

    /** @deprecated since 1.x, read policy()->rights->attribution->text */
    public ?string $attribution;

    /**
     * @var list<string>
     *
     * @deprecated since 1.x, read policy()->provenance (terms, policy)
     */
    public array $terms;

    private UsagePolicy $policy;

    /**
     * @param bool               $search       it searches by place (SearchInterface)
     * @param bool               $availability it gives a property's availability and offers (AvailabilityInterface)
     * @param bool               $calendar     it gives a property's nights one by one (CalendarInterface)
     * @param bool               $book         it books an offer (BookInterface)
     * @param bool               $cancel       it cancels a booking (CancelInterface)
     * @param bool               $content      it describes a property whole (ContentInterface)
     * @param list<string>       $searchBy     how its search reads a place: "place" (a text), "cityCode" (IATA), "point" (latitude, longitude, radius)
     * @param list<PropertyKind> $kinds        the kinds of property it holds
     * @param bool               $pricedSearch its search answers with the stay's price when the query has dates
     * @param bool               $cardPayment  it takes a card to pay or guarantee a booking (BookingRequest::$card)
     * @param bool               $keyless      it needs no account nor key
     * @param bool               $sandbox      it is configured on a test environment: nothing it books is real
     * @param ?string            $attribution  what its terms ask to be shown beside its records
     * @param list<string>       $terms        the terms whoever runs it must follow (URLs)
     * @param ?UsagePolicy       $policy       its usage policy; made of $attribution, $terms and $keyless when null
     */
    public function __construct(
        public bool $search = false,
        public bool $availability = false,
        public bool $calendar = false,
        public bool $book = false,
        public bool $cancel = false,
        public bool $content = false,
        public array $searchBy = [],
        public array $kinds = [],
        public bool $pricedSearch = false,
        public bool $cardPayment = false,
        bool $keyless = false,
        public bool $sandbox = false,
        ?string $attribution = null,
        array $terms = [],
        ?UsagePolicy $policy = null,
    ) {
        $this->policy = $policy ?? UsagePolicy::described(UsagePolicy::UNNAMED, $attribution, null, $terms, $keyless);
        $this->attribution = $attribution ?? $policy?->rights->attribution->text;
        $this->terms = [] !== $terms || null === $policy ? $terms : array_values(array_unique(array_filter([$policy->provenance->terms, $policy->provenance->policy])));
        $this->keyless = $keyless || (null !== $policy && !$policy->requirements->key);
    }

    public function policy(): UsagePolicy
    {
        return $this->policy;
    }

    /** Whether its search can read the query's place: a text, a city code or a point it reads. */
    public function reads(StayQuery $query): bool
    {
        return $this->search && (
            (null !== $query->place && \in_array('place', $this->searchBy, true))
            || (null !== $query->cityCode && \in_array('cityCode', $this->searchBy, true))
            || ($query->hasPoint() && \in_array('point', $this->searchBy, true))
        );
    }

    public function holds(PropertyKind $kind): bool
    {
        return \in_array($kind, $this->kinds, true);
    }

    /** @return list<string> what it can be asked, by contract: search, availability, calendar, book, cancel, content */
    public function contracts(): array
    {
        return array_keys(array_filter([
            'search' => $this->search, 'availability' => $this->availability, 'calendar' => $this->calendar,
            'book' => $this->book, 'cancel' => $this->cancel, 'content' => $this->content,
        ]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contracts' => $this->contracts(),
            'searchBy' => $this->searchBy,
            'kinds' => array_map(static fn (PropertyKind $kind) => $kind->value, $this->kinds),
            'pricedSearch' => $this->pricedSearch,
            'cardPayment' => $this->cardPayment,
            'keyless' => $this->keyless,
            'sandbox' => $this->sandbox,
            'attribution' => $this->attribution,
            'terms' => $this->terms,
            'policy' => $this->policy->toArray(),
        ];
    }
}
