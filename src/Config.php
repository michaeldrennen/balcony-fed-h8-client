<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client;

final class Config
{
    public const DEFAULT_URL = 'https://www.federalreserve.gov/datadownload/Output.aspx?rel=H8&filetype=csv';

    public function __construct(
        public readonly string $url = self::DEFAULT_URL,
        public readonly float $timeoutSeconds = 30.0,
    ) {
    }
}
