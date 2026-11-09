<?php

namespace Omnistay\Model;

/**
 * What cancelling costs: free until $freeUntil (a moment in the
 * property's time, with its offset), then $penalty (or $penaltyPercent of
 * the total); a non-refundable rate has neither deadline nor refund.
 * $text is the provider's own wording, shown as given.
 */
final readonly class CancellationPolicy
{
    public function __construct(
        public bool $refundable = true,
        public ?\DateTimeImmutable $freeUntil = null,
        public ?Money $penalty = null,
        public ?int $penaltyPercent = null,
        public ?string $text = null,
        public ?string $code = null,
    ) {
    }

    public static function nonRefundable(?string $text = null): self
    {
        return new self(false, text: $text, penaltyPercent: 100);
    }

    /** Whether cancelling at this moment costs nothing. */
    public function isFreeAt(\DateTimeInterface $moment): bool
    {
        if (!$this->refundable) {
            return false;
        }

        return null === $this->freeUntil ? null === $this->penalty && null === $this->penaltyPercent : $moment <= $this->freeUntil;
    }

    /** What cancelling at this moment costs, out of the total paid. */
    public function penaltyAt(\DateTimeInterface $moment, Money $total): Money
    {
        if ($this->isFreeAt($moment)) {
            return Money::zero($total->currency);
        }
        if (!$this->refundable) {
            return $total;
        }
        if (null !== $this->penalty && $this->penalty->currency === $total->currency) {
            return $this->penalty->compare($total) > 0 ? $total : $this->penalty;
        }

        return $total->percent($this->penaltyPercent ?? 100);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['refundable' => $this->refundable, 'freeUntil' => $this->freeUntil?->format(\DATE_ATOM), 'penalty' => $this->penalty?->toArray(), 'penaltyPercent' => $this->penaltyPercent, 'text' => $this->text, 'code' => $this->code];
    }
}
