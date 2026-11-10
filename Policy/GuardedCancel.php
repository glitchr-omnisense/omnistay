<?php

namespace Omnistay\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnistay\CancelInterface;
use Omnistay\Model\Booking;
use Omnistay\ProviderInterface;

/**
 * A provider's cancellations behind its guard: each call is the operation
 * "cancel".
 *
 * @extends Guarded<CancelInterface>
 */
final class GuardedCancel extends Guarded implements CancelInterface
{
    public function __construct(CancelInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function cancel(string $reference, ?string $reason = null): Booking
    {
        return $this->guard->call('cancel', fn () => $this->provider->cancel($reference, $reason));
    }
}
