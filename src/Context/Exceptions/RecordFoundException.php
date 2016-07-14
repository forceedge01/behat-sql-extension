<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class RecordFoundException extends Exception
{
    public function __construct($criteria, $table)
    {
        parent::__construct(sprintf(
            'Unexpected record found with "%s" in "%s".',
            $criteria,
            $table
        ));
    }
}
