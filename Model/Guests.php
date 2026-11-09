<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/** Who sleeps there: adults, and the children by their age (a cot is not a bed). */
final readonly class Guests
{
    /** @param list<int> $childAges */
    public function __construct(public int $adults = 1, public array $childAges = [], public int $pets = 0)
    {
        if ($adults < 1) {
            throw new InvalidArgumentException('A stay has at least one adult.');
        }
        foreach ($childAges as $age) {
            if ($age < 0 || $age > 17) {
                throw new InvalidArgumentException(\sprintf('A child is 0 to 17 years old, not %d.', $age));
            }
        }
        if ($pets < 0) {
            throw new InvalidArgumentException('No negative pets.');
        }
    }

    public static function adults(int $adults): self
    {
        return new self($adults);
    }

    public function children(): int
    {
        return \count($this->childAges);
    }

    /** Everyone, children of every age included: what a listing's capacity counts. */
    public function count(): int
    {
        return $this->adults + $this->children();
    }

    /** @return array{adults: int, childAges: list<int>, pets: int} */
    public function toArray(): array
    {
        return ['adults' => $this->adults, 'childAges' => $this->childAges, 'pets' => $this->pets];
    }
}
