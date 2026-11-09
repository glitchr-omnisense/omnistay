<?php

namespace Omnistay\Exception;

/** A provider asked for what it does not do: a calendar of a hotel system, a cancellation where the provider offers none. */
final class NotSupportedException extends \LogicException implements OmnistayException
{
    public static function capability(object $provider, string $name, string $capability): self
    {
        return new self(\sprintf('The "%s" provider (%s) does not %s.', $name, $provider::class, $capability));
    }
}
