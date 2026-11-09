<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/**
 * An amount in a currency's minor units (cents): 12050 EUR is 120,50 €.
 * Every price of the family is one: a night, a fee, a tax, a total.
 */
final readonly class Money
{
    public string $currency;

    public function __construct(public int $amount, string $currency)
    {
        $currency = strtoupper(trim($currency));
        if (!preg_match('~^[A-Z]{3}$~', $currency)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not an ISO 4217 currency code.', $currency));
        }
        $this->currency = $currency;
    }

    public static function of(int $amount, string $currency): self
    {
        return new self($amount, $currency);
    }

    public static function zero(string $currency): self
    {
        return new self(0, $currency);
    }

    /** From a provider's decimal ("215.05"), to minor units. */
    public static function fromDecimal(string|int|float $decimal, string $currency): self
    {
        return new self((int) round(((float) $decimal) * (10 ** self::minorDigits($currency))), $currency);
    }

    /** In major units, as a provider's decimal API expects ("215.05"); yen and the like have none. */
    public function decimal(): string
    {
        $digits = self::minorDigits($this->currency);

        return number_format($this->amount / (10 ** $digits), $digits, '.', '');
    }

    public function add(self ...$others): self
    {
        $amount = $this->amount;
        foreach ($others as $other) {
            $amount += $this->same($other)->amount;
        }

        return new self($amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        return new self($this->amount - $this->same($other)->amount, $this->currency);
    }

    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    /** A share of it, in percent (12.5 for 12,5 %), rounded to the minor unit. */
    public function percent(int|float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    public function isZero(): bool
    {
        return 0 === $this->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function compare(self $other): int
    {
        return $this->amount <=> $this->same($other)->amount;
    }

    /** How many minor digits a currency has: 2 for most, 0 for JPY, KRW..., 3 for BHD, KWD... */
    public static function minorDigits(string $currency): int
    {
        return match (strtoupper($currency)) {
            'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'UYI', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' => 0,
            'BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND' => 3,
            default => 2,
        };
    }

    /** @return array{amount: int, currency: string, decimal: string} */
    public function toArray(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency, 'decimal' => $this->decimal()];
    }

    public function __toString(): string
    {
        return $this->decimal().' '.$this->currency;
    }

    private function same(self $other): self
    {
        return $other->currency === $this->currency ? $other : throw new InvalidArgumentException(\sprintf('%s and %s are not in the same currency.', $this, $other));
    }
}
