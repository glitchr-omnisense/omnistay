<?php

namespace Omnistay\Model;

/**
 * Some of the answers, and the token that asks for the next ones (null:
 * the last page). $total is how many there are in all when the provider
 * says so.
 *
 * @template T
 */
final readonly class Page
{
    /** @param list<T> $items */
    public function __construct(public array $items, public ?string $next = null, public ?int $total = null)
    {
    }
}
