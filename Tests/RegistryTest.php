<?php

namespace Omnistay\Tests;

use Omnistay\Capabilities;
use Omnistay\Config;
use Omnistay\Exception\InvalidConfigException;
use Omnistay\Exception\NotSupportedException;
use Omnistay\Exception\ProviderException;
use Omnistay\Exception\UnavailableException;
use Omnistay\Model\BookingRequest;
use Omnistay\Model\Guests;
use Omnistay\Model\Location;
use Omnistay\Model\Money;
use Omnistay\Model\Page;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyKind;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Stay;
use Omnistay\Model\StayQuery;
use Omnistay\Model\Traveller;
use Omnistay\ProviderFactory;
use Omnistay\ProviderInterface;
use Omnistay\Registry;
use Omnistay\SearchInterface;
use Omnistay\Testing\FixedProviderFactory;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testAProviderIsBuiltOnceUnderItsName(): void
    {
        $registry = $this->registry();

        self::assertSame($registry->get('homes'), $registry->get('homes'));
        self::assertSame('homes', $registry->get('homes')->getName(), 'the name it is configured under');
        self::assertSame(['hotels', 'homes', 'down'], $registry->names());
        self::assertSame(['fixed', 'down'], $registry->factories());
    }

    public function testAnUnknownProviderOrFactoryIsNamed(): void
    {
        try {
            $this->registry()->get('cars');
            self::fail('no exception');
        } catch (InvalidConfigException $e) {
            self::assertSame('No "cars" provider; configured: hotels, homes, down.', $e->getMessage());
        }
        $this->expectExceptionMessage('No "amadeus" factory for the "hotels" provider; installed: none.');
        (new Registry([], ['hotels' => ['factory' => 'amadeus']]))->get('hotels');
    }

    public function testACapabilityItLacksIsRefusedBeforeAnyCall(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('does not book');
        $this->registry()->book('down');
    }

    public function testOneSearchOverEveryProviderThatReadsThePlace(): void
    {
        $results = $this->registry()->searchAll(new StayQuery(Stay::of('2026-12-18', '2026-12-20'), new Guests(2), place: 'Annecy'));

        self::assertSame(['hotels', 'homes', 'down'], $results->asked);
        self::assertSame(['homes:L-1', 'hotels:H-1'], array_map(static fn ($r) => (string) $r->property->ref, $results->items), 'the cheaper night first');
        self::assertSame(['down' => 'Down answered 503 for GET /search. Maintenance'], $results->failures, 'one provider failing leaves the others standing');
        self::assertCount(1, $results->of('homes'));
    }

    public function testAProviderThatCannotReadThePlaceIsNotAsked(): void
    {
        $results = $this->registry()->searchAll(new StayQuery(cityCode: 'NCE'), 'homes', 'down');

        self::assertSame(['homes'], $results->asked, 'down reads a text only');
        self::assertSame([], $results->items);
    }

    public function testAPropertyIsAskedToTheProviderItCameFrom(): void
    {
        $registry = $this->registry();
        $ref = new PropertyRef('homes', 'L-1');
        $stay = Stay::of('2026-12-18', '2026-12-20');
        $offer = $registry->availabilityOf($ref, $stay, new Guests(2))->cheapest();
        self::assertNotNull($offer);
        self::assertSame('Cabane', $registry->propertyOf($ref)?->name);

        $booking = $registry->bookOffer(new BookingRequest($offer, [new Traveller('Alice', 'Example')]));
        self::assertSame(['confirmed', 19000], [$booking->status->value, $booking->total?->amount]);

        $this->expectException(UnavailableException::class);
        $registry->bookOffer(new BookingRequest($offer, [new Traveller('Bob', 'Example')]));
    }

    private function registry(): Registry
    {
        $annecy = new Location(45.9, 6.13, city: 'Annecy', countryCode: 'FR');
        $fixed = new FixedProviderFactory([
            new Property(new PropertyRef('hotels', 'H-1'), 'Hôtel', PropertyKind::Hotel, $annecy),
            new Property(new PropertyRef('homes', 'L-1'), 'Cabane', PropertyKind::Cabin, $annecy),
        ], ['H-1' => Money::of(14000, 'EUR'), 'L-1' => Money::of(9500, 'EUR')]);
        $down = new class extends ProviderFactory {
            protected function populate(Config $c): void
            {
                $c->defaults(['omnistay.factory_name' => 'down', 'omnistay.factory_title' => 'Down']);
            }

            protected function build(Config $c): ProviderInterface
            {
                return new class implements ProviderInterface, SearchInterface {
                    public function getName(): string
                    {
                        return 'down';
                    }

                    public function getTitle(): string
                    {
                        return 'Down';
                    }

                    public function capabilities(): Capabilities
                    {
                        return new Capabilities(search: true, searchBy: ['place']);
                    }

                    public function search(StayQuery $query): Page
                    {
                        throw ProviderException::answered('Down', 'GET /search', 503, 'Maintenance');
                    }
                };
            }
        };

        return new Registry([$fixed, $down], ['hotels' => ['factory' => 'fixed'], 'homes' => ['factory' => 'fixed'], 'down' => ['factory' => 'down']]);
    }
}
