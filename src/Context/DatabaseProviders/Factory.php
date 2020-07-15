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
    /**
     * @var array Available providers.
     */
    private $providers;

    /**
     * @var array instantiated providers.
     */
    private $instantiated;

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
        if (isset($this->instantiated[$engine])) {
            return $this->instantiated[$engine];
        }

        $providerClass = $this->getClass($engine);

        if (!$providerClass) {
            throw new Exception("Provider for engine '$engine' not found.");
        }

        $this->instantiated[$engine] = new $providerClass($executor);

        return $this->instantiated[$engine];
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
        if (!is_subclass_of($class, DatabaseProviderInterface::class)) {
            throw new Exception(sprintf(
                'Provider class \'%s\' must implement \'%s\'',
                $class,
                DatabaseProviderInterface::class
            ));
        }

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
