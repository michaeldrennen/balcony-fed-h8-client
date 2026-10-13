<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balcony_fed_bank_balance_sheets', function (Blueprint $table): void {
            $table->id();
            $table->date('report_date')->unique('balcony_fed_report_date_unique');
            $table->decimal('cash_assets_billions', 12, 3);
            $table->decimal('treasury_agency_securities_billions', 12, 3);
            $table->decimal('mortgage_backed_securities_billions', 12, 3);
            $table->decimal('total_loans_leases_billions', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balcony_fed_bank_balance_sheets');
    }
};
