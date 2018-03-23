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
}
