<?php

namespace Omnistay\Model;

/**
 * A car to hire for the stay: its supplier, its category (the ACRISS code,
 * "CDMR": compact, 4-5 doors, manual, air conditioning), where and when it
 * is picked up and returned, its rate and cancellation policy. A model the
 * family shares; the provider that searches and books cars comes with its
 * first site.
 */
final readonly class CarHire
{
    public function __construct(
        public string $id,
        public string $provider,
        public string $supplier,
        public string $category,
        public Location $pickUp,
        public \DateTimeImmutable $pickUpAt,
        public Location $dropOff,
        public \DateTimeImmutable $dropOffAt,
        public Rate $rate,
        public CancellationPolicy $cancellation = new CancellationPolicy(),
        public ?string $model = null,
        public ?int $seats = null,
        public ?string $transmission = null,
        public bool $unlimitedMileage = true,
    ) {
    }

    /** Days hired, a day started counting whole. */
    public function days(): int
    {
        $seconds = $this->dropOffAt->getTimestamp() - $this->pickUpAt->getTimestamp();

        return max(1, (int) ceil($seconds / 86400));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'provider' => $this->provider, 'supplier' => $this->supplier, 'category' => $this->category, 'pickUp' => $this->pickUp->toArray(), 'pickUpAt' => $this->pickUpAt->format(\DATE_ATOM), 'dropOff' => $this->dropOff->toArray(), 'dropOffAt' => $this->dropOffAt->format(\DATE_ATOM), 'days' => $this->days(), 'rate' => $this->rate->toArray(), 'cancellation' => $this->cancellation->toArray(), 'model' => $this->model, 'seats' => $this->seats, 'transmission' => $this->transmission, 'unlimitedMileage' => $this->unlimitedMileage];
    }
}
