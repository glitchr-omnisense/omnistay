<?php

namespace Omnistay;

/**
 * A provider's factory fills a Config - its name ("omnistay.factory_name"),
 * its title ("omnistay.factory_title"), the options it needs
 * ("omnistay.required_options") and the defaults of the others - then builds
 * the provider from it. The registry adds "omnistay.name", the name the
 * provider is configured under, which its PropertyRefs carry.
 *
 * Nothing here calls anything: a factory whose provider is reached over
 * HTTP takes its client in its own constructor - the application's, a
 * MockHttpClient in a test.
 */
abstract class ProviderFactory implements ProviderFactoryInterface
{
    public function getName(): string
    {
        return $this->createConfig()->string('omnistay.factory_name') ?? static::class;
    }

    public function create(array $options = []): ProviderInterface
    {
        $config = $this->createConfig($options);
        $required = $config->get('omnistay.required_options', []);
        $config->validateNotEmpty(\is_array($required) ? array_values(array_filter($required, 'is_string')) : []);

        return $this->build($config);
    }

    /** @param array<string, mixed> $options */
    public function createConfig(array $options = []): Config
    {
        $config = new Config($options);
        $this->populate($config);
        $config->defaults(['omnistay.name' => $config->string('omnistay.factory_name')]);

        return $config;
    }

    /** The provider's name in the registry: what its PropertyRefs carry. */
    protected static function name(Config $c): string
    {
        return $c->string('omnistay.name') ?? $c->string('omnistay.factory_name') ?? 'stays';
    }

    /** The factory's name and title, its required options, the defaults of the others. */
    abstract protected function populate(Config $c): void;

    /** The provider, from a Config that holds everything it needs. */
    abstract protected function build(Config $c): ProviderInterface;
}
