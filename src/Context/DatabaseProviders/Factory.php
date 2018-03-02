<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderInterface;

/**
 * Factory class.
 */
class Factory
{
    /**
     * @param string $engine
     * @param DBManagerInterface $executor
     *
     * @return DatabaseProviderInterface
     */
    public function getProvider($engine, DBManagerInterface $executor)
    {
        return new $engine($executor);
    }
}
