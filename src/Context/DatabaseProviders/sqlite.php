<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * sqlite class.
 */
class sqlite extends BaseProvider
{
    /**
     * {@inheritDoc}
     */
    public function getConnectionString($dbname, $host)
    {
        throw new \Exception('Method getConnectionString() is not implemented.');
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKeyForTable($database, $schema, $table)
    {
        throw new \Exception('Method getPrimaryKeyForTable() is not implemented.');
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredTableColumns($database, $schema, $table)
    {
        throw new \Exception('Method getRequiredTableColumns() is not implemented.');
    }
}
