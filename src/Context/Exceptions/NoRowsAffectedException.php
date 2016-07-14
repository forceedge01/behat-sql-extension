<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class NoRowsAffectedException extends Exception
{
    /**
     * @param string $sql The SQL executed.
     * @param string $error The error received.
     */
    public function __construct($sql, $error)
    {
        parent::__construct(sprintf(
            'No rows were effected!%sSQL: "%s",%sError: %s',
            PHP_EOL,
            $sql,
            PHP_EOL,
            $error
        ));
    }
}
