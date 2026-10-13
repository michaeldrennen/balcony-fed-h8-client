<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FedH8Client;

final class Config
{
    /**
     * Fed Data Download Program preformatted package "All Commercial Banks, SA (Weekly)",
     * limited to the last 52 observations. Clear lastobs to download the full history.
     */
    public const DEFAULT_URL = 'https://www.federalreserve.gov/datadownload/Output.aspx?rel=H8'
        . '&series=17951c643555bee48d63bb6957a4a92e&lastobs=52&from=&to=&filetype=csv'
        . '&label=include&layout=seriescolumn';

    public function __construct(
        public readonly string $url = self::DEFAULT_URL,
        public readonly float $timeoutSeconds = 30.0,
    ) {
    }
}
