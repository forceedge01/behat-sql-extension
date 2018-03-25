<?php

namespace Genesis\SQLExtension\Context\Interfaces;

/**
 * DatabaseProviderFactory interface.
 */
interface DatabaseProviderFactoryInterface
{
    /**
     * @param string $engineProvider
     * @param DBManagerInterface $executor
     *
     * @return DatabaseProviderInterface
     */
    public function getProvider($engineProvider, DBManagerInterface $executor);

    /**
     * This method can be extended to register custom providers.
     *
     * @param string $engine
     *
     * @return string
     */
    public function getClass($engine);
}
