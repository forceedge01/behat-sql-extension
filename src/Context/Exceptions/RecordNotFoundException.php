<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class RecordNotFoundException extends Exception
{
    public function __construct($criteria, $table)
    {
        parent::__construct(sprintf(
            'Record not found with "%s" in "%s"',
            $criteria,
            $table
        ));
    }
}
