<?php

namespace Omnistay\Model;

use Omnistay\Exception\InvalidArgumentException;

/**
 * A property as one provider knows it: the provider's name in the
 * registry ("hotels", "homes") and its identifier there ("ARMADAIT", a
 * listing's id). Written "hotels:ARMADAIT", read back by parse(): the
 * registry sends every question about it to that provider.
 */
final readonly class PropertyRef implements \Stringable
{
    public function __construct(public string $provider, public string $id)
    {
        if ('' === $provider || '' === $id || str_contains($provider, ':')) {
            throw new InvalidArgumentException(\sprintf('A property is "provider:id", not "%s:%s".', $provider, $id));
        }
    }

    public static function parse(string $ref): self
    {
        $parts = explode(':', $ref, 2);

        return 2 === \count($parts) ? new self($parts[0], $parts[1]) : throw new InvalidArgumentException(\sprintf('A property is "provider:id", not "%s".', $ref));
    }

    public function equals(self $other): bool
    {
        return $this->provider === $other->provider && $this->id === $other->id;
    }

    public function __toString(): string
    {
        return $this->provider.':'.$this->id;
    }
}
