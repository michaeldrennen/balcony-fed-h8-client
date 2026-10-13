<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client\Tests\Integration;

use DateTimeImmutable;
use MichaelDrennen\Balcony\FedH8Client\Client;
use MichaelDrennen\Balcony\FedH8Client\Config;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Hits the real remote source. Excluded from the default run; use `vendor/bin/phpunit --group integration`.
 */
#[Group('integration')]
final class LiveFetchTest extends TestCase
{
    private const MAX_AGE_DAYS = 21;

    public function testFetchesRecentDataFromLiveSource(): void
    {
        $dtos = (new Client(new Config()))->fetchBalanceSheets();

        $this->assertNotEmpty($dtos, 'Expected the H.8 bank balance sheet release from the Federal Reserve.');

        $newest = max(array_map(static fn ($dto): DateTimeImmutable => $dto->reportDate, $dtos));
        $ageDays = (int) $newest->diff(new DateTimeImmutable())->format('%a');

        $this->assertLessThanOrEqual(
            self::MAX_AGE_DAYS,
            $ageDays,
            sprintf('Newest record is from %s; the source may have stopped updating.', $newest->format('Y-m-d')),
        );
    }
}
