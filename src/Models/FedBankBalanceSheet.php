<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client\Models;

use Illuminate\Database\Eloquent\Model;

final class FedBankBalanceSheet extends Model
{
    protected $table = 'balcony_fed_bank_balance_sheets';

    protected $guarded = ['id'];

    protected $casts = [
        'report_date' => 'date',
        'cash_assets_billions' => 'decimal:3',
        'treasury_agency_securities_billions' => 'decimal:3',
        'mortgage_backed_securities_billions' => 'decimal:3',
        'total_loans_leases_billions' => 'decimal:3',
    ];
}
