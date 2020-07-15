<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderInterface;

/**
 * base class.
 */
abstract class BaseProvider implements DatabaseProviderInterface
{
    /**
     * @var DBManagerInterface
     */
    private $executor;

    /**
     * @param DBManagerInterface $executor
     */
    public function __construct(DBManagerInterface $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @return DBManagerInterface
     */
    public function getExecutor()
    {
        return $this->executor;
    }

    /**
     * {@inheritDoc}
     */
    public function getLeftDelimiterForReservedWord()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getRightDelimiterForReservedWord()
    {
        return '';
    }

    abstract public function getPrimaryKeyForTable($database, $schema, $table);

    abstract public function getRequiredTableColumns($database, $schema, $table);

    abstract public function getTableColumns($database, $schema, $table);
}
