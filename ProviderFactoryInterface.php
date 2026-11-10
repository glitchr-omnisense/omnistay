<?php

namespace Omnistay;

/** Builds a provider from its options (a key, a secret, a store...). */
interface ProviderFactoryInterface extends \Omnisense\Contracts\Provider\ProviderFactoryInterface
{
    /** The name providers are configured with: "amadeus", "local"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): ProviderInterface;
}
