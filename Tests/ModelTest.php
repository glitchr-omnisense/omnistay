<?php

namespace Omnistay\Tests;

use Omnistay\Exception\InvalidArgumentException;
use Omnistay\Model\Amenity;
use Omnistay\Model\CancellationPolicy;
use Omnistay\Model\CarHire;
use Omnistay\Model\Charge;
use Omnistay\Model\ChargeKind;
use Omnistay\Model\Guests;
use Omnistay\Model\Location;
use Omnistay\Model\Money;
use Omnistay\Model\Offer;
use Omnistay\Model\Package;
use Omnistay\Model\PaymentCard;
use Omnistay\Model\Property;
use Omnistay\Model\PropertyKind;
use Omnistay\Model\PropertyRef;
use Omnistay\Model\Rate;
use Omnistay\Model\SearchResult;
use Omnistay\Model\Stay;
use Omnistay\Model\StayQuery;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testMoneyInMinorUnits(): void
    {
        self::assertSame('215.05', Money::fromDecimal('215.05', 'eur')->decimal());
        self::assertSame(21505, Money::fromDecimal('215.05', 'EUR')->amount);
        self::assertSame('12000', Money::fromDecimal('12000', 'JPY')->decimal());
        self::assertSame('1.500', Money::of(1500, 'KWD')->decimal());
        self::assertSame(1250, Money::of(10000, 'EUR')->percent(12.5)->amount);
        self::assertSame('300.00 EUR', (string) Money::of(10000, 'EUR')->multiply(3));
        self::assertSame(1, Money::of(2, 'EUR')->compare(Money::of(1, 'EUR')));

        $this->expectException(InvalidArgumentException::class);
        Money::of(1, 'EUR')->add(Money::of(1, 'JPY'));
    }

    public function testACurrencyIsThreeLetters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of(1, 'euro');
    }

    public function testAStayCountsItsNights(): void
    {
        $stay = Stay::of('2026-12-30', '2027-01-02');

        self::assertSame(3, $stay->count());
        self::assertSame(['2026-12-30', '2026-12-31', '2027-01-01'], array_map(static fn (\DateTimeImmutable $d) => $d->format('Y-m-d'), $stay->dates()));
        self::assertTrue($stay->contains(new \DateTimeImmutable('2027-01-01 23:00')));
        self::assertFalse($stay->contains(new \DateTimeImmutable('2027-01-02')), 'the morning of the check-out is not a night');
        self::assertFalse($stay->overlaps(Stay::of('2027-01-02', '2027-01-05')), 'a check-in on the check-out day shares no night');
        self::assertTrue($stay->overlaps(Stay::of('2027-01-01', '2027-01-05')));
        self::assertTrue(Stay::nights('2026-12-30', 3)->equals($stay));
        self::assertSame(['checkIn' => '2026-12-30', 'checkOut' => '2027-01-02', 'nights' => 3], $stay->toArray());
    }

    public function testAStayEndsAfterItStarts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Stay::of('2026-12-30', '2026-12-30');
    }

    public function testADateIsADate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Stay::of('2026-02-30', '2026-03-02');
    }

    public function testGuestsAreAnAdultAtLeastAndChildrenByAge(): void
    {
        $guests = new Guests(2, [4, 9]);
        self::assertSame([4, 2], [$guests->count(), $guests->children()]);

        $this->expectException(InvalidArgumentException::class);
        new Guests(0);
    }

    public function testARateAddsItsLinesUp(): void
    {
        $rate = new Rate(Money::of(33000, 'EUR'), [
            new Charge(ChargeKind::Nights, Money::of(30000, 'EUR')),
            new Charge(ChargeKind::Cleaning, Money::of(4000, 'EUR')),
            new Charge(ChargeKind::Discount, Money::of(-3000, 'EUR'), 'Week'),
            new Charge(ChargeKind::Tax, Money::of(2000, 'EUR'), 'Tourist tax'),
            new Charge(ChargeKind::Tax, Money::of(5000, 'EUR'), 'VAT', included: true),
        ]);

        self::assertTrue($rate->addsUp(), 'the included VAT left out');
        self::assertSame(33000, $rate->sum()->amount);
        self::assertSame(30000, $rate->base()->amount);
        self::assertCount(2, $rate->charges(ChargeKind::Tax));
        self::assertFalse((new Rate(Money::of(35000, 'EUR'), $rate->charges))->addsUp(), 'a total the lines do not tell');
    }

    public function testACancellationIsFreeUntilItsDeadline(): void
    {
        $policy = new CancellationPolicy(true, new \DateTimeImmutable('2026-12-10T23:59:00+01:00'), Money::of(9500, 'EUR'));
        $total = Money::of(28500, 'EUR');

        self::assertTrue($policy->isFreeAt(new \DateTimeImmutable('2026-12-10T22:00:00Z')));
        self::assertFalse($policy->isFreeAt(new \DateTimeImmutable('2026-12-11T00:00:00+01:00')));
        self::assertSame(0, $policy->penaltyAt(new \DateTimeImmutable('2026-12-01'), $total)->amount);
        self::assertSame(9500, $policy->penaltyAt(new \DateTimeImmutable('2026-12-15'), $total)->amount);
        self::assertSame(28500, CancellationPolicy::nonRefundable()->penaltyAt(new \DateTimeImmutable('2026-12-01'), $total)->amount);
        self::assertSame(14250, (new CancellationPolicy(true, new \DateTimeImmutable('2026-12-10'), penaltyPercent: 50))->penaltyAt(new \DateTimeImmutable('2026-12-12'), $total)->amount);
        self::assertTrue((new CancellationPolicy())->isFreeAt(new \DateTimeImmutable()), 'no deadline, no penalty: always free');
    }

    public function testAQueryFiltersResults(): void
    {
        $query = new StayQuery(Stay::of('2026-12-18', '2026-12-21'), new Guests(3), latitude: 45.9, longitude: 6.13, radius: 5, kinds: [PropertyKind::Cabin, PropertyKind::House], amenities: [Amenity::Kitchen], maxNightly: Money::of(12000, 'EUR'), minRating: 4.5);

        self::assertTrue($query->accepts(new SearchResult(self::home(), Money::of(28500, 'EUR'), 3)));
        self::assertFalse($query->accepts(new SearchResult(self::home(), Money::of(39000, 'EUR'), 3)), '130 a night');
        self::assertTrue($query->accepts(new SearchResult(self::home(), Money::of(39000, 'USD'), 3)), 'priced in another currency: kept');
        self::assertFalse($query->accepts(new SearchResult(self::home([Amenity::Wifi]))), 'no kitchen');
        self::assertFalse($query->accepts(new SearchResult(self::home(rating: null))), 'no rating');
        self::assertFalse($query->accepts(new SearchResult(self::home(max: 2))), 'sleeps two');
        self::assertFalse($query->accepts(new SearchResult(self::home(lat: 46.2))), '30 km away');
        self::assertFalse($query->accepts(new SearchResult(self::home(kind: PropertyKind::Hotel))));
    }

    public function testAPropertyRefNamesItsProvider(): void
    {
        $ref = PropertyRef::parse('hotels:ARMADAIT');
        self::assertSame(['hotels', 'ARMADAIT'], [$ref->provider, $ref->id]);
        self::assertSame('homes:a:b', (string) PropertyRef::parse('homes:a:b'));

        $this->expectException(InvalidArgumentException::class);
        PropertyRef::parse('ARMADAIT');
    }

    public function testACardNumberIsNeverPrinted(): void
    {
        $card = new PaymentCard('VI', '4151 2897 2247 1370', '2027-08', 'ALICE EXAMPLE', '123');

        self::assertSame('1370', $card->last4());
        self::assertStringNotContainsString('41512897', json_encode($card->toArray()) ?: '');
        self::assertStringNotContainsString('2247', print_r($card, true));
        self::assertStringNotContainsString('123', print_r($card, true));
    }

    public function testAPackageAndACar(): void
    {
        $ref = new PropertyRef('hotels', 'H1');
        $stay = Stay::of('2026-12-18', '2026-12-21');
        $offer = new Offer('O1', $ref, $stay, new Guests(2), new Rate(Money::of(42000, 'EUR')));
        $car = new CarHire('C1', 'cars', 'Example Cars', 'CDMR', new Location(city: 'Annecy'), new \DateTimeImmutable('2026-12-18T10:00:00Z'), new Location(city: 'Annecy'), new \DateTimeImmutable('2026-12-21T09:00:00Z'), new Rate(Money::of(12000, 'EUR')));
        $package = new Package('P1', 'packages', 'Three nights and a car', new Rate(Money::of(50000, 'EUR')), [$offer], [$car], ['A boat tour']);

        self::assertSame(3, $car->days());
        self::assertSame(54000, $package->partsTotal()->amount);
        self::assertSame('CDMR', $package->cars[0]->category);
        self::assertSame(3, $package->toArray()['cars'] ? $car->toArray()['days'] : 0);

        $this->expectException(InvalidArgumentException::class);
        new Package('P2', 'packages', 'Nothing', new Rate(Money::of(1, 'EUR')));
    }

    /** @param list<Amenity> $amenities */
    private static function home(array $amenities = [Amenity::Kitchen, Amenity::Wifi], ?float $rating = 4.8, int $max = 4, float $lat = 45.91, PropertyKind $kind = PropertyKind::Cabin): Property
    {
        return new Property(new PropertyRef('homes', 'L-1'), 'Cabane', $kind, new Location($lat, 6.14), amenities: $amenities, rating: $rating, maxGuests: $max);
    }
}
