<?php

namespace Omnistay\Model;

/**
 * A card a provider takes to guarantee or pay a booking itself (a hotel
 * through Amadeus). Its number never leaves in toArray() nor in a
 * message: only the last four digits are kept for display. A site that
 * takes the payment itself (omnitrade, the guest on the payment
 * provider's page) sends no card at all: BookingRequest::$paymentReference.
 */
final readonly class PaymentCard
{
    public function __construct(
        public string $vendorCode,
        #[\SensitiveParameter] public string $number,
        public string $expiry,
        public string $holder,
        #[\SensitiveParameter] public ?string $securityCode = null,
    ) {
    }

    public function last4(): string
    {
        return substr(preg_replace('~\D~', '', $this->number) ?? '', -4);
    }

    /** @return array{vendorCode: string, last4: string, expiry: string, holder: string} */
    public function toArray(): array
    {
        return ['vendorCode' => $this->vendorCode, 'last4' => $this->last4(), 'expiry' => $this->expiry, 'holder' => $this->holder];
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
