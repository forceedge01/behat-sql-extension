<?php

namespace Genesis\SQLExtension\Context\Exceptions;

use Exception;

class ExternalRefResolutionException extends Exception
{
    /**
     * @param string $externalRef
     * @param string $query
     */
    public function __construct($externalRef, $query)
    {
        parent::__construct(sprintf(
            'Unable to fetch value for external ref.%sExternalRef: "%s"%sQuery: "%s"%s',
            PHP_EOL . PHP_EOL,
            $externalRef,
            PHP_EOL,
            $query,
            PHP_EOL
        ));
    }
}
