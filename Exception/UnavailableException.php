<?php

namespace Omnistay\Exception;

/**
 * The nights asked are not free: booked by someone else, blocked by the
 * host, too few for the minimum stay, too many guests. $reason is one of
 * the family's words: booked, blocked, min_stay, max_stay, capacity,
 * closed, expired.
 */
final class UnavailableException extends \RuntimeException implements OmnistayException
{
    public function __construct(string $message, public readonly string $reason = 'booked', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function because(string $reason, string $property, string $stay): self
    {
        return new self(\sprintf('%s is not available for %s (%s).', $property, $stay, $reason), $reason);
    }
}
