<?php

namespace Tests\Base;

use Psr\Log\AbstractLogger;

class StdoutLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $datetime = date('Y-m-d H:i:s');
        $formatted = sprintf("[%s] - %s - %s\n", $datetime, $level, $message);
        fwrite(STDOUT, $formatted);
    }
}
