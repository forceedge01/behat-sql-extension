<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;

class SelectException extends BaseException
{
    const CODE = 0;

    /**
     * @param string $dataProperty The property that was not found.
     * @param BaseException $e The original exception.
     */
    public function __construct($table, BaseException $e)
    {
        $message = "Unable to select data from table '$table', Error " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
