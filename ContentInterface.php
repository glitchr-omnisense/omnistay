<?php

namespace Omnistay;

use Omnistay\Model\Property;
use Omnistay\Model\PropertyRef;

interface ContentInterface
{
    /** A property's whole description; null when the provider does not know it. */
    public function property(PropertyRef $property, ?string $language = null): ?Property;

    /**
     * Several at once, in as few calls as the provider allows; those it does
     * not know left out.
     *
     * @param list<PropertyRef> $properties
     *
     * @return list<Property>
     */
    public function properties(array $properties, ?string $language = null): array;
}
