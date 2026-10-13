<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client\DTO;

use DateTimeImmutable;

final class FedBankBalanceSheetDTO implements MetricObservation
{
    public function __construct(
        public readonly DateTimeImmutable $reportDate,
        public readonly float $cashAssetsBillions,
        public readonly float $treasuryAgencySecuritiesBillions,
        public readonly float $mortgageBackedSecuritiesBillions,
        public readonly float $totalLoansLeasesBillions,
    ) {
    }

    public function asModelAttributes(): array
    {
        return [
            'report_date' => $this->reportDate->format('Y-m-d'),
            'cash_assets_billions' => $this->cashAssetsBillions,
            'treasury_agency_securities_billions' => $this->treasuryAgencySecuritiesBillions,
            'mortgage_backed_securities_billions' => $this->mortgageBackedSecuritiesBillions,
            'total_loans_leases_billions' => $this->totalLoansLeasesBillions,
        ];
    }
}
