<?php

namespace Omnistay\Model;

/** What one night costs, before fees and taxes: the night of $date. */
final readonly class NightRate
{
    public function __construct(public \DateTimeImmutable $date, public Money $price)
    {
    }

    /** @return array{date: string, price: array{amount: int, currency: string, decimal: string}} */
    public function toArray(): array
    {
        return ['date' => $this->date->format('Y-m-d'), 'price' => $this->price->toArray()];
    }
}
