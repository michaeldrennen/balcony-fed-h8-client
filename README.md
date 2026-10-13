# balcony-fed-h8-client

PHP 8.1+ client for the Federal Reserve Board H.8 release ("Assets and Liabilities of Commercial Banks in the United States"), part of The Balcony macro flow system.

## Usage (framework-agnostic)

```php
use MichaelDrennen\Balcony\FedH8Client\Client;
use MichaelDrennen\Balcony\FedH8Client\Config;

$client = new Client(new Config(url: 'https://www.federalreserve.gov/datadownload/Output.aspx?rel=H8&series=YOUR_SERIES_KEY&filetype=csv'));

foreach ($client->fetchBalanceSheets() as $dto) {
    // $dto->reportDate, $dto->cashAssetsBillions, $dto->treasuryAgencySecuritiesBillions, ...
}
```

The default URL is the H.8 CSV download endpoint. Pass a `series=` key for the specific series you need. Rows with a missing or non-numeric value are skipped. Results are returned in file order.

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
vendor/bin/phpunit
```
