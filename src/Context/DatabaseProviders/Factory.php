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

    public function __construct()
    {
        $this->registerProvider('mysql', mysql::class);
        $this->registerProvider('dblib', dblib::class);
        $this->registerProvider('mssql', mssql::class);
        $this->registerProvider('odbc', odbc::class);
        $this->registerProvider('pgsql', pgsql::class);
        $this->registerProvider('sqlite', sqlite::class);
        $this->registerProvider('sybase', sybase::class);
    }

    /**
     * @param string $engine
     * @param DBManagerInterface $executor
     *
     * @return DatabaseProviderInterface
     */
    public function getProvider($engine, DBManagerInterface $executor)
    {
        $providerClass = $this->getClass($engine);

        if (!$providerClass) {
            throw new Exception("Provider for engine '$engine' not found.");
        }

        return new $providerClass($executor);
    }

    /**
     * Register a custom class.
     *
     * @param string $name
     * @param string $class
     *
     * @return string
     */
    public function registerProvider($name, $class)
    {
        $this->providers[$name] = $class;

        return $this;
    }

    /**
     * @param string $engine
     *
     * @return string|null
     */
    public function getClass($engine)
    {
        return isset($this->providers[$engine]) ? $this->providers[$engine] : null;
    }
}
