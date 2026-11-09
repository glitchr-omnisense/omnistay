<?php

namespace Omnistay\Tests;

use Omnistay\Http\Data;
use Omnistay\Registry;
use Omnistay\Testing\BareTestCase;

/**
 * omnisense/omnistay with no framework: the harness's bare script searches
 * hotels and homes in one query, prices a stay, books it, refuses the same
 * nights to a second guest, cancels, and makes a provider's JSON call, in a
 * PHP process of its own - nothing may load from outside the package but
 * Symfony's HTTP client contracts (interfaces only).
 */
final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return Registry::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }

    public function testAStayBookedByHandLoadsNothingButOmnistayAndWhatItRequires(): void
    {
        $report = Data::of(self::php([__DIR__.'/../docker/harness/bin/bare']));

        self::assertSame(['search', 'availability', 'calendar', 'book', 'cancel', 'content'], $report->get('capabilities.contracts'));
        self::assertSame([['homes:L-17', 'cabin', '285.00', '95.00'], ['hotels:H-LAC', 'hotel', '420.00', '140.00']], $report->get('search'));
        self::assertSame(['hotels', 'homes'], $report->get('asked'));
        self::assertSame([true, 'L-17|2026-12-18|2026-12-21|2', '285.00', 3], $report->get('offer'));
        self::assertSame('blocked', $report->get('blocked'));
        self::assertSame(['FX-1', 'confirmed', '2026-12-18/2026-12-21'], $report->get('booking'));
        self::assertSame('booked', $report->get('second'));
        self::assertSame([['12-20', false, 'booked'], ['12-21', true, null], ['12-22', true, null], ['12-23', true, null], ['12-24', false, 'blocked']], $report->get('calendar'));
        self::assertSame('cancelled', $report->get('cancelled'));
        self::assertTrue($report->get('free-again'));
        self::assertSame(['Hôtel du Lac Example', 45.8992, ['WIFI'], 'omnistay-bare/1.0'], $report->get('json'));
        self::assertNull($report->get('json-404'));
        self::assertSame('Example answered 500 for GET /hotels/3. Internal error', $report->get('json-error'));

        $symbols = $report->strings('loaded.symbols');
        self::assertContains('Omnistay\Testing\FixedProvider', $symbols);
        self::assertSame([], self::foreign($symbols), 'no class of a framework, an HTTP client or an application');
        self::assertSame([], self::outside($report->strings('loaded.files'), $report->strings('loaded.eager')), 'no file but the package and what it requires');
    }
}
