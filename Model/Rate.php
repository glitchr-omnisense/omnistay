<?php

namespace Omnistay\Model;

/**
 * What a stay costs, line by line: the nights (each with its price when
 * the provider gives them), the fees, the taxes, the discounts - and the
 * total the guest pays. The lines not $included add up to the total; a
 * provider that gives the total alone gives one Nights line.
 */
final readonly class Rate
{
    /**
     * @param list<Charge>    $charges
     * @param list<NightRate> $nights
     */
    public function __construct(public Money $total, public array $charges = [], public array $nights = [])
    {
    }

    /** The lines added to the total, those included in it left out. */
    public function sum(): Money
    {
        $sum = Money::zero($this->total->currency);
        foreach ($this->charges as $charge) {
            if (!$charge->included) {
                $sum = $sum->add($charge->amount);
            }
        }

        return $sum;
    }

    /** Whether the lines tell the whole total: then a page may list them. */
    public function addsUp(): bool
    {
        return [] !== $this->charges && $this->sum()->equals($this->total);
    }

    /** @return list<Charge> the lines of a kind */
    public function charges(ChargeKind $kind): array
    {
        return array_values(array_filter($this->charges, static fn (Charge $charge) => $kind === $charge->kind));
    }

    /** The nights' line: what the stay costs before fees and taxes. */
    public function base(): Money
    {
        $base = Money::zero($this->total->currency);
        foreach ($this->charges(ChargeKind::Nights) as $charge) {
            $base = $base->add($charge->amount);
        }

        return $base;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['total' => $this->total->toArray(), 'charges' => array_map(static fn (Charge $c) => $c->toArray(), $this->charges), 'nights' => array_map(static fn (NightRate $n) => $n->toArray(), $this->nights)];
    }
}
