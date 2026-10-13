CREATE TABLE IF NOT EXISTS balcony_fed_bank_balance_sheets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_date DATE NOT NULL,
    cash_assets_billions DECIMAL(12,3) NOT NULL,
    treasury_agency_securities_billions DECIMAL(12,3) NOT NULL,
    mortgage_backed_securities_billions DECIMAL(12,3) NOT NULL,
    total_loans_leases_billions DECIMAL(12,3) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY balcony_fed_report_date_unique (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
