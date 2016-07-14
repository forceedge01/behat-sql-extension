<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class RecordNotFoundException extends Exception
{
    /**
     * @param string $criteria The criteria.
     * @param string $table The table on which the criteria is applied.
     */
    public function __construct($criteria, $table)
    {
        parent::__construct(sprintf(
            'Record not found with "%s" in "%s"',
            $criteria,
            $table
        ));
    }
}
