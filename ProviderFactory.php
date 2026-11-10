<?php

namespace Omnistay;

use Omnisense\Contracts\Provider\Config as BaseConfig;
use Omnisense\Contracts\Provider\ProviderFactory as BaseFactory;
use Omnistay\Exception\InvalidConfigException;
use Omnistay\Exception\UsageLimitReachedException;

/**
 * A provider's factory fills a Config - its name ("omnistay.factory_name"),
 * its title ("omnistay.factory_title"), the options it needs
 * ("omnistay.required_options") and the defaults of the others - then builds
 * the provider from it. The registry adds "omnistay.name", the name the
 * provider is configured under, which its PropertyRefs carry (the factory's
 * name outside a registry). omnisense/contracts' factory underneath gives the
 * Config the provider's Backoff (Config::backoff(), for its JsonClient) and,
 * once built, its PolicyGuard (guard()).
 *
 * Nothing here calls anything: a factory whose provider is reached over
 * HTTP takes its client in its own constructor - the application's, a
 * MockHttpClient in a test.
 */
abstract class ProviderFactory extends BaseFactory implements ProviderFactoryInterface
{
    public function create(array $options = []): ProviderInterface
    {
        $provider = parent::create($options);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" factory built no omnistay provider.', $this->getName()));
    }

    /** @param array<string, mixed> $options */
    public function createConfig(array $options = []): Config
    {
        return self::own(parent::createConfig($options));
    }

    /** The factory's name and title, its required options, the defaults of the others. */
    abstract protected function populate(Config $c): void;

    /** The provider, from a Config that holds everything it needs. */
    abstract protected function build(Config $c): ProviderInterface;

    protected function newConfig(array $options): Config
    {
        return new Config($options);
    }

    protected function limitException(): string
    {
        return UsageLimitReachedException::class;
    }

    final protected function configure(BaseConfig $c): void
    {
        $this->populate(self::own($c));
    }

    final protected function make(BaseConfig $c): ProviderInterface
    {
        $config = self::own($c);
        $config->defaults(['omnistay.name' => $config->string('omnistay.factory_name')]);

        return $this->build($config);
    }

    /** The provider's name in the registry: what its PropertyRefs carry. */
    protected static function name(Config $c): string
    {
        return $c->string('omnistay.name') ?? $c->string('omnistay.factory_name') ?? 'stays';
    }

    private static function own(BaseConfig $c): Config
    {
        return $c instanceof Config ? $c : throw new InvalidConfigException('An omnistay factory builds from omnistay\'s Config.');
    }
}
