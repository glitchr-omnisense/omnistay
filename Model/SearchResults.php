<?php

namespace Omnistay\Model;

/**
 * What several providers answered to one query, merged
 * (Registry::searchAll()): the results, and the providers that failed,
 * with their message - a provider out of reach leaves the others' answers
 * standing.
 */
final readonly class SearchResults implements \Countable
{
    /**
     * @param list<SearchResult>    $items
     * @param array<string, string> $failures by provider name
     * @param list<string>          $asked    the providers asked
     */
    public function __construct(public array $items, public array $failures = [], public array $asked = [])
    {
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /** @return list<SearchResult> those of a provider */
    public function of(string $provider): array
    {
        return array_values(array_filter($this->items, static fn (SearchResult $r) => $provider === $r->property->ref->provider));
    }
}
