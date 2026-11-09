<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/**
 * A stay sold together with what goes with it - a car, an activity, the
 * journey there described as text - at one price. Its total may be lower
 * than its parts' (the package's discount, as a Discount charge of its
 * rate).
 */
final readonly class Package
{
    /**
     * @param list<Offer>   $stays
     * @param list<CarHire> $cars
     * @param list<string>  $extras what else it holds, as the seller words it
     */
    public function __construct(
        public string $id,
        public string $provider,
        public string $title,
        public Rate $rate,
        public array $stays = [],
        public array $cars = [],
        public array $extras = [],
        public CancellationPolicy $cancellation = new CancellationPolicy(),
    ) {
        if ([] === $stays && [] === $cars && [] === $extras) {
            throw new InvalidArgumentException('A package holds something.');
        }
    }

    /** What the parts would cost one by one, in the package's currency (parts in another left out). */
    public function partsTotal(): Money
    {
        $sum = Money::zero($this->rate->total->currency);
        foreach ([...array_map(static fn (Offer $o) => $o->total(), $this->stays), ...array_map(static fn (CarHire $c) => $c->rate->total, $this->cars)] as $total) {
            if ($total->currency === $sum->currency) {
                $sum = $sum->add($total);
            }
        }

        return $sum;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'provider' => $this->provider, 'title' => $this->title, 'rate' => $this->rate->toArray(), 'stays' => array_map(static fn (Offer $o) => $o->toArray(), $this->stays), 'cars' => array_map(static fn (CarHire $c) => $c->toArray(), $this->cars), 'extras' => $this->extras, 'cancellation' => $this->cancellation->toArray(), 'partsTotal' => $this->partsTotal()->toArray()];
    }
}
