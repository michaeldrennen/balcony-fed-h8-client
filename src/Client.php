<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MichaelDrennen\Balcony\FedH8Client\DTO\FedBankBalanceSheetDTO;
use MichaelDrennen\Balcony\FedH8Client\Exceptions\BalconyClientException;

final class Client
{
    private const COL_DATE = 'time period';
    private const COL_CASH = 'cash assets (billion $)';
    private const COL_TREASURY = 'treasury and agency securities (billion $)';
    private const COL_MBS = 'mortgage-backed securities (billion $)';
    private const COL_LOANS = 'total loans and leases (billion $)';

    private readonly ClientInterface $http;

    public function __construct(
        private readonly Config $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $config->timeoutSeconds,
            'http_errors' => false,
        ]);
    }

    /**
     * @return FedBankBalanceSheetDTO[]
     */
    public function fetchBalanceSheets(): array
    {
        try {
            $response = $this->http->request('GET', $this->config->url);
        } catch (GuzzleException $e) {
            throw BalconyClientException::transport($e->getMessage(), $e);
        }

        $status = $response->getStatusCode();

        if ($status === 429) {
            $retryHeader = $response->getHeaderLine('Retry-After');
            throw BalconyClientException::rateLimited(is_numeric($retryHeader) ? (int) $retryHeader : null);
        }

        if ($status >= 400) {
            throw BalconyClientException::httpError($status, (string) $response->getBody());
        }

        return $this->parse((string) $response->getBody());
    }

    /**
     * Parse the H.8 CSV. Metadata lines before the header are ignored. The header is located by
     * its "Time Period" column, and column names are matched case-insensitively.
     *
     * @return FedBankBalanceSheetDTO[]
     */
    public function parse(string $csv): array
    {
        $csv = ltrim($csv, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\r|\n/', $csv) ?: [];

        $columns = null;
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line);

            if ($columns === null) {
                $header = array_map(static fn (string $h): string => strtolower(trim($h)), $cells);

                if (in_array(self::COL_DATE, $header, true)) {
                    self::assertColumns($header);
                    $columns = array_flip($header);
                }

                continue;
            }

            $reportDate = Client::parseDate($cells[$columns[self::COL_DATE]] ?? null);
            $cash = Client::parseNumber($cells[$columns[self::COL_CASH]] ?? null);
            $treasury = Client::parseNumber($cells[$columns[self::COL_TREASURY]] ?? null);
            $mbs = Client::parseNumber($cells[$columns[self::COL_MBS]] ?? null);
            $loans = Client::parseNumber($cells[$columns[self::COL_LOANS]] ?? null);

            if ($reportDate === null || $cash === null || $treasury === null || $mbs === null || $loans === null) {
                continue;
            }

            $rows[] = new FedBankBalanceSheetDTO(
                reportDate: $reportDate,
                cashAssetsBillions: $cash,
                treasuryAgencySecuritiesBillions: $treasury,
                mortgageBackedSecuritiesBillions: $mbs,
                totalLoansLeasesBillions: $loans,
            );
        }

        if ($columns === null) {
            throw BalconyClientException::malformed('H.8 CSV header with "Time Period" was not found.');
        }

        return $rows;
    }

    /**
     * @param string[] $header
     */
    private static function assertColumns(array $header): void
    {
        foreach ([self::COL_CASH, self::COL_TREASURY, self::COL_MBS, self::COL_LOANS] as $required) {
            if (!in_array($required, $header, true)) {
                throw BalconyClientException::malformed(sprintf('H.8 CSV is missing column "%s".', $required));
            }
        }
    }

    public static function parseNumber(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        return ($trimmed !== '' && is_numeric($trimmed)) ? (float) $trimmed : null;
    }

    public static function parseDate(?string $raw): ?DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $trimmed);

        return ($date !== false && $date->format('Y-m-d') === $trimmed) ? $date : null;
    }
}
