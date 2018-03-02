<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * pgsql class.
 */
class pgsql extends mysql
{
    /**
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbname, $host, $port = 5432)
    {
        return sprintf(
            'pgsql:dbname=%s;host=%s;port=%s',
            $dbname,
            $host,
            $port
        );
    }
}
