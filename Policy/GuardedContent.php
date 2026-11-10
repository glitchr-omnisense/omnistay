<?php

namespace Omnistay\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omnistay\ContentInterface;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyRef;
use Omnistay\ProviderInterface;

/**
 * A provider's descriptions behind its guard: each call is the operation of its
 * method's name ("property", "properties").
 *
 * @extends Guarded<ContentInterface>
 */
final class GuardedContent extends Guarded implements ContentInterface
{
    public function __construct(ContentInterface&ProviderInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function property(PropertyRef $property, ?string $language = null): ?Property
    {
        return $this->guard->call('property', fn () => $this->provider->property($property, $language));
    }

    public function properties(array $properties, ?string $language = null): array
    {
        return $this->guard->call('properties', fn () => $this->provider->properties($properties, $language));
    }
}
