<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class DeleteException extends Exception
{
    const CODE = 0;

    /**
     * @param string $dataProperty The property that was not found.
     * @param BaseException $e The original exception.
     */
    public function __construct($table, BaseException $e)
    {
        $message = "Unable to delete data from table '$table', Error: " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
