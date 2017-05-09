<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception as BaseException;

class DeleteException extends BaseException
{
    const CODE = 0;

    /**
     * @param string $table The property that was not found.
     * @param BaseException $e The original exception.
     * @param mixed $table
     */
    public function __construct($table, BaseException $e)
    {
        $message = "Unable to delete data from table '$table', Error: " . $e->getMessage();
        parent::__construct($message, self::CODE, $e);
    }
}
