<?php

namespace Omnistay\Model;

/**
 * Something that can be booked: a room type (or the whole home) at a
 * property, for these dates and guests, at this rate, under this
 * cancellation policy. Its $id is the provider's - Amadeus' offer id, a
 * local quote's - and may expire ($expiresAt); AvailabilityInterface::offer()
 * gives it again as it stands now, re-priced.
 */
final readonly class Offer
{
    public function __construct(
        public string $id,
        public PropertyRef $property,
        public Stay $stay,
        public Guests $guests,
        public Rate $rate,
        public CancellationPolicy $cancellation = new CancellationPolicy(),
        public ?RoomType $roomType = null,
        public ?Board $board = null,
        public PaymentTiming $payment = PaymentTiming::Now,
        public int $rooms = 1,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?string $rateCode = null,
        public ?string $description = null,
        public bool $instant = true,
    ) {
    }

    public function total(): Money
    {
        return $this->rate->total;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'property' => (string) $this->property,
            'stay' => $this->stay->toArray(),
            'guests' => $this->guests->toArray(),
            'rate' => $this->rate->toArray(),
            'cancellation' => $this->cancellation->toArray(),
            'roomType' => $this->roomType?->toArray(),
            'board' => $this->board?->value,
            'payment' => $this->payment->value,
            'rooms' => $this->rooms,
            'expiresAt' => $this->expiresAt?->format(\DATE_ATOM),
            'rateCode' => $this->rateCode,
            'description' => $this->description,
            'instant' => $this->instant,
        ];
    }
}
