<?php

namespace Genesis\SQLExtension\Context\Interfaces;

interface DebuggerInterface
{
    /**
     * log.
     *
     * @param $message The message to print if debugging is on.
     */
    public static function log($message);
}
