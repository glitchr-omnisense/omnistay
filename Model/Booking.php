<?php

namespace Omnistay\Model;

/**
 * A booking as the provider keeps it: its reference there, the
 * property's own confirmation numbers, its status, the stay and guests,
 * what was paid, the offer it books. $raw keeps the provider's whole
 * answer, for what this model leaves out.
 */
final readonly class Booking
{
    /**
     * @param list<string>         $confirmations the property's or the chain's numbers
     * @param list<Traveller>      $travellers
     * @param array<mixed>         $raw
     */
    public function __construct(
        public string $reference,
        public BookingStatus $status,
        public PropertyRef $property,
        public Stay $stay,
        public Guests $guests,
        public ?Money $total = null,
        public array $confirmations = [],
        public array $travellers = [],
        public ?Offer $offer = null,
        public ?CancellationPolicy $cancellation = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?string $clientReference = null,
        public array $raw = [],
    ) {
    }

    /** The same booking in another status: what CancelInterface gives back. */
    public function withStatus(BookingStatus $status): self
    {
        return new self($this->reference, $status, $this->property, $this->stay, $this->guests, $this->total, $this->confirmations, $this->travellers, $this->offer, $this->cancellation, $this->createdAt, $this->clientReference, $this->raw);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'property' => (string) $this->property,
            'stay' => $this->stay->toArray(),
            'guests' => $this->guests->toArray(),
            'total' => $this->total?->toArray(),
            'confirmations' => $this->confirmations,
            'travellers' => array_map(static fn (Traveller $t) => $t->toArray(), $this->travellers),
            'offer' => $this->offer?->id,
            'cancellation' => $this->cancellation?->toArray(),
            'createdAt' => $this->createdAt?->format(\DATE_ATOM),
            'clientReference' => $this->clientReference,
        ];
    }
}
