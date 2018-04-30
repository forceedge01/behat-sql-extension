<?php

namespace Genesis\SQLExtension\Context;

class Debugger implements Interfaces\DebuggerInterface
{
    /**
     * @const int
     */
    const MODE_SQL_ONLY = 2;

    /**
     * @const int
     */
    const MODE_ALL = 1;

    /**
     * @var boolean
     */
    private static $debugMode = false;

    /**
     * log.
     *
     * @param string $message The message to print if debugging is on.
     */
    public static function log($message)
    {
        switch (self::$debugMode) {
            case self::MODE_SQL_ONLY:
                if (strpos($message, 'SQL') !== false) {
                    self::printToScreen($message);
                }
                break;
            case self::MODE_ALL:
                self::printToScreen($message);
                break;
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private static function printToScreen($message)
    {
        echo 'DEBUG >>> ' . $message . PHP_EOL;
    }

    /**
     * @param int $mode
     *
     * @return
     */
    public static function enable($mode)
    {
        self::$debugMode = $mode;
    }

    /**
     * @return void
     */
    public static function disable()
    {
        self::$debugMode = false;
    }
}
