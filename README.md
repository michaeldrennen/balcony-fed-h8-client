# balcony-fed-h8-client

PHP 8.1+ client for the Federal Reserve Board H.8 release ("Assets and Liabilities of Commercial Banks in the United States"), part of The Balcony macro flow system.

## Usage (framework-agnostic)

```php
use MichaelDrennen\Balcony\FedH8Client\Client;
use MichaelDrennen\Balcony\FedH8Client\Config;

$client = new Client(new Config());

foreach ($client->fetchBalanceSheets() as $dto) {
    // $dto->reportDate, $dto->cashAssetsBillions, $dto->treasuryAgencySecuritiesBillions, ...
}
```

The default URL is the Fed Data Download Program package "All Commercial Banks, SA (Weekly)", last 52 weeks. Remove `lastobs=52` from the URL (pass your own `url:`) to download the full history.

Fields are converted from the Fed's millions to billions and come from these H.8 series (all commercial banks, seasonally adjusted):

| DTO field | Series | Description |
|---|---|---|
| `cashAssetsBillions` | `B1048NCBA` | Cash assets |
| `treasuryAgencySecuritiesBillions` | `B1003NCBA` | Treasury and agency securities |
| `mortgageBackedSecuritiesBillions` | `B1301NCBA` | Treasury and agency securities: MBS (agency MBS only) |
| `totalLoansLeasesBillions` | `B1020NCBA` | Loans and leases in bank credit |

Weeks with a missing value (`ND`) or a non-numeric value are skipped. Results are returned oldest first, in file order.

## Laravel persistence

```php
use MichaelDrennen\Balcony\FedH8Client\Models\FedBankBalanceSheet;

foreach ($client->fetchBalanceSheets() as $dto) {
    FedBankBalanceSheet::updateOrCreate(
        ['report_date' => $dto->reportDate->format('Y-m-d')],
        $dto->asModelAttributes(),
    );
}
```

## Errors

Failures throw `MichaelDrennen\Balcony\FedH8Client\Exceptions\BalconyClientException`. HTTP 429 sets `isRateLimited()` and exposes `retryAfterSeconds`.

## Tests

```
composer install
vendor/bin/phpunit                      # offline unit tests
vendor/bin/phpunit --group integration  # live download from federalreserve.gov
```
