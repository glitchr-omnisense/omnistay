<?php

namespace Omnistay\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnistay\Model\Page;
use Omnistay\Model\StayQuery;
use Omnistay\ProviderInterface;
use Omnistay\SearchInterface;

/**
 * A provider's search behind its guard: each call is the operation
 * "search".
 *
 * @extends Guarded<SearchInterface>
 */
final class GuardedSearch extends Guarded implements SearchInterface
{
    public function __construct(SearchInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function search(StayQuery $query): Page
    {
        return $this->guard->call('search', fn () => $this->provider->search($query));
    }
}
