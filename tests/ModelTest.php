<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client\Tests;

use DateTimeImmutable;
use MichaelDrennen\Balcony\FedH8Client\DTO\FedBankBalanceSheetDTO;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testDtoMapsToModelAttributes(): void
    {
        $dto = new FedBankBalanceSheetDTO(new DateTimeImmutable('2026-10-02'), 3240.5, 4610.2, 2745.8, 12480.1);

        $this->assertSame([
            'report_date' => '2026-10-02',
            'cash_assets_billions' => 3240.5,
            'treasury_agency_securities_billions' => 4610.2,
            'mortgage_backed_securities_billions' => 2745.8,
            'total_loans_leases_billions' => 12480.1,
        ], $dto->asModelAttributes());
    }

    public function testEloquentModelAcceptsDtoAttributes(): void
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            $this->markTestSkipped('illuminate/database is not installed.');
        }

        // Eloquent's guarded-attribute check reads the column listing, so the model needs a connection.
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $dto = new FedBankBalanceSheetDTO(new DateTimeImmutable('2026-10-02'), 1.0, 2.0, 3.0, 4.0);
        $model = new \MichaelDrennen\Balcony\FedH8Client\Models\FedBankBalanceSheet($dto->asModelAttributes());

        $this->assertSame('balcony_fed_bank_balance_sheets', $model->getTable());
        $this->assertSame(['id'], $model->getGuarded());
    }
}
