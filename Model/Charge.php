<?php

namespace Omnistay\Model;

/**
 * One line of a price: the nights, a cleaning fee, a tax... $included
 * says the amount is already inside the total (a VAT the hotel's price
 * holds) rather than added to it. A discount is negative.
 */
final readonly class Charge
{
    public function __construct(
        public ChargeKind $kind,
        public Money $amount,
        public ?string $label = null,
        public bool $included = false,
        public ?string $code = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'amount' => $this->amount->toArray(), 'label' => $this->label, 'included' => $this->included, 'code' => $this->code];
    }
}
