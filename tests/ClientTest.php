<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MichaelDrennen\Balcony\FedH8Client\Client;
use MichaelDrennen\Balcony\FedH8Client\Config;
use MichaelDrennen\Balcony\FedH8Client\Exceptions\BalconyClientException;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/sample_response.csv');
    }

    public function testParsesRowsAfterMetadataAndSkipsNonNumeric(): void
    {
        $dtos = (new Client(new Config()))->parse($this->fixture());

        $this->assertCount(2, $dtos);
        $this->assertSame('2026-10-02', $dtos[0]->reportDate->format('Y-m-d'));
        $this->assertSame(3240.5, $dtos[0]->cashAssetsBillions);
        $this->assertSame(12480.1, $dtos[0]->totalLoansLeasesBillions);
    }

    public function testFetchUsesMockedHttp(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $this->fixture())])),
        ]);

        $this->assertCount(2, (new Client(new Config(), $http))->fetchBalanceSheets());
    }

    public function testRateLimitThrows(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(429, ['Retry-After' => '20'], '')])),
            'http_errors' => false,
        ]);

        try {
            (new Client(new Config(), $http))->fetchBalanceSheets();
            $this->fail('Expected BalconyClientException.');
        } catch (BalconyClientException $e) {
            $this->assertTrue($e->isRateLimited());
            $this->assertSame(20, $e->retryAfterSeconds);
        }
    }

    public function testMissingHeaderThrows(): void
    {
        $this->expectException(BalconyClientException::class);
        (new Client(new Config()))->parse("foo,bar\n1,2\n");
    }
}
