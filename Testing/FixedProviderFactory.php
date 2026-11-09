<?php

namespace Omnistay\Testing;

use Omnistay\Config;
use Omnistay\Model\Money;
use Omnistay\Model\Property;
use Omnistay\ProviderFactory;
use Omnistay\ProviderInterface;

/** FixedProvider's factory: "fixed", answering from the properties it holds. */
final class FixedProviderFactory extends ProviderFactory
{
    /**
     * @param list<Property>              $properties
     * @param array<string, Money>        $nightly
     * @param array<string, list<string>> $blocked
     */
    public function __construct(private readonly array $properties = [], private readonly array $nightly = [], private readonly array $blocked = [])
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omnistay.factory_name' => 'fixed', 'omnistay.factory_title' => 'Fixed stays', 'omnistay.required_options' => []]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new FixedProvider($this->properties, $this->nightly, $this->blocked, self::name($c));
    }
}
