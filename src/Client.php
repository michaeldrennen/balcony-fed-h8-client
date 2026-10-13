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
    private const ROW_MULTIPLIER = 'multiplier:';

    // H.8 series codes, all commercial banks, seasonally adjusted.
    private const SERIES_CASH = 'B1048NCBA';
    private const SERIES_TREASURY = 'B1003NCBA';
    private const SERIES_MBS = 'B1301NCBA';
    private const SERIES_LOANS = 'B1020NCBA';

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
     * Parse a Fed Data Download Program H.8 CSV (layout=seriescolumn, label=include).
     * Metadata rows come first; the "Time Period" row names each column by series code,
     * and the "Multiplier:" row gives each column's unit, which is converted to billions.
     * Rows with a missing or non-numeric value (the Fed uses "ND") are skipped.
     *
     * @return FedBankBalanceSheetDTO[]
     */
    public function parse(string $csv): array
    {
        $csv = ltrim($csv, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\r|\n/', $csv) ?: [];

        $multipliers = [];
        $columns = null;
        $scales = [];
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = array_map('trim', str_getcsv($line));

            if ($columns === null) {
                $label = strtolower($cells[0]);

                if ($label === self::ROW_MULTIPLIER) {
                    $multipliers = $cells;
                } elseif ($label === self::COL_DATE) {
                    $columns = self::locateSeries($cells);
                    $scales = self::billionScales($columns, $multipliers);
                }

                continue;
            }

            $reportDate = Client::parseDate($cells[0]);
            $values = [];

            foreach ($columns as $series => $index) {
                $number = Client::parseNumber($cells[$index] ?? null);
                $values[$series] = $number === null ? null : round($number * $scales[$series], 4);
            }

            if ($reportDate === null || in_array(null, $values, true)) {
                continue;
            }

            $rows[] = new FedBankBalanceSheetDTO(
                reportDate: $reportDate,
                cashAssetsBillions: $values[self::SERIES_CASH],
                treasuryAgencySecuritiesBillions: $values[self::SERIES_TREASURY],
                mortgageBackedSecuritiesBillions: $values[self::SERIES_MBS],
                totalLoansLeasesBillions: $values[self::SERIES_LOANS],
            );
        }

        if ($columns === null) {
            throw BalconyClientException::malformed('H.8 CSV header with "Time Period" was not found.');
        }

        return $rows;
    }

    /**
     * @param string[] $header
     * @return array<string, int> series code => column index
     */
    private static function locateSeries(array $header): array
    {
        $columns = [];

        foreach ([self::SERIES_CASH, self::SERIES_TREASURY, self::SERIES_MBS, self::SERIES_LOANS] as $series) {
            $index = array_search($series, $header, true);

            if (!is_int($index)) {
                throw BalconyClientException::malformed(sprintf('H.8 CSV is missing series "%s".', $series));
            }

            $columns[$series] = $index;
        }

        return $columns;
    }

    /**
     * @param array<string, int> $columns
     * @param string[] $multipliers
     * @return array<string, float> series code => factor that converts a raw value to billions
     */
    private static function billionScales(array $columns, array $multipliers): array
    {
        $scales = [];

        foreach ($columns as $series => $index) {
            $multiplier = Client::parseNumber($multipliers[$index] ?? null);

            if ($multiplier === null || $multiplier <= 0) {
                throw BalconyClientException::malformed(sprintf('H.8 CSV has no multiplier for series "%s".', $series));
            }

            $scales[$series] = $multiplier / 1_000_000_000;
        }

        return $scales;
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
