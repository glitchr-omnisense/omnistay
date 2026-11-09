<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/**
 * Book this offer for these travellers. The first traveller is the
 * contact. A provider that takes the money itself is given a $card; a
 * site that took it already (through omnitrade) gives the payment's
 * reference instead. $clientReference is the site's own (its booking's
 * number), sent where the provider keeps one; $idempotencyKey makes the
 * same request sent twice book once, where the provider honours one.
 */
final readonly class BookingRequest
{
    /** @param list<Traveller> $travellers */
    public function __construct(
        public Offer $offer,
        public array $travellers,
        public ?PaymentCard $card = null,
        public ?string $paymentReference = null,
        public ?string $clientReference = null,
        public ?string $message = null,
        public ?string $idempotencyKey = null,
    ) {
        if ([] === $travellers) {
            throw new InvalidArgumentException('A booking names at least one traveller.');
        }
    }

    public function contact(): Traveller
    {
        return $this->travellers[0];
    }
}
