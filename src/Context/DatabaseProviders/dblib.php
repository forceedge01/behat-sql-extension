<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * dblib class.
 */
class dblib extends BaseProvider
{
    /**
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbname, $host)
    {
        throw new \Exception('Method getPdoDnsString() is not implemented.');
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
