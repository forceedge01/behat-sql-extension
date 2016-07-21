<?php

namespace Genesis\SQLExtension\Context;

class Debugger implements Interfaces\DebuggerInterface
{
    /**
     * log.
     *
     * @param $message The message to print if debugging is on.
     */
    public static function log($message)
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE == 1) {
            echo 'DEBUG >>> ' . $message . PHP_EOL;
        }
    }
}
