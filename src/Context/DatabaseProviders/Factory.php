<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

use Exception;
use Genesis\SQLExtension\Context\Interfaces\DBManagerInterface;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderFactoryInterface;
use Genesis\SQLExtension\Context\Interfaces\DatabaseProviderInterface;

/**
 * Factory class.
 */
class Factory implements DatabaseProviderFactoryInterface
{
    private $providers;

    /**
     * @param string $engineProvider
     * @param DBManagerInterface $executor
     *
     * @return DatabaseProviderInterface
     */
    public function getProvider($engineProvider, DBManagerInterface $executor)
    {
        if (! class_exists($engineProvider)) {
            throw new Exception("Provider for engine '$engineProvider' not found.");
        }

        return new $engineProvider($executor);
    }

    /**
     * This method can be extended to register custom providers.
     *
     * @param string $engine
     *
     * @return string
     */
    public function getClass($engine)
    {
        return '\Genesis\SQLExtension\Context\DatabaseProviders\\' . $engine;
    }
}
