<?php

namespace Tests\Support;

/**
 * Helper used by the external/integration tests to detect whether a third-party
 * SOAP host is reachable. When it is not (offline CI, host retired, etc.) the
 * tests skip instead of failing, keeping the build green.
 */
final class RemoteHost
{
    public static function isReachable(string $host, int $port = 80, float $timeout = 3.0): bool
    {
        $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($conn) {
            fclose($conn);
            return true;
        }

        return false;
    }
}
