<?php

namespace Genesis\SQLExtension;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Extension.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class Extension implements ExtensionInterface
{
    /**
     * Load and set the configuration options.
     */
    public function load(ContainerBuilder $container, array $config)
    {
        if (isset($config['connection_details'])) {
            DEFINE('SQLDBENGINE', $config['connection_details']['engine']);
            DEFINE('SQLDBHOST', $config['connection_details']['host']);
            DEFINE('SQLDBPORT', $config['connection_details']['port']);
            DEFINE('SQLDBSCHEMA', $config['connection_details']['schema']);
            DEFINE('SQLDBNAME', $config['connection_details']['dbname']);
            DEFINE('SQLDBUSERNAME', $config['connection_details']['username']);
            DEFINE('SQLDBPASSWORD', $config['connection_details']['password']);
            DEFINE('SQLDBPREFIX', $config['connection_details']['dbprefix']);
            session_start();
            // Store any keywords set in behat.yml file
            if (isset($config['keywords']) && $config['keywords']) {
                foreach ($config['keywords'] as $keyword => $value) {
                    $_SESSION['behat']['GenesisSqlExtension']['keywords'][$keyword] = $value;
                }
            }

            // Set 'notQuotableKeywords' for later use.
            $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

            if (isset($config['notQuotableKeywords'])) {
                $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = $config['notQuotableKeywords'];
            }

            if (isset($config['connection_details']['connection_options'])) {
                $options = [];

                foreach($config['connection_details']['connection_options'] as $option=>$value) {
                    $options[constant('\PDO::' . $option)] = $value;
                }

                $_SESSION['behat']['GenesisSqlExtension']['connection_details']['connection_options'] = $options;
            } else {
                $_SESSION['behat']['GenesisSqlExtension']['connection_details']['connection_options'] = [];
            }
        }

        // Check if we need to enable debug mode. This is not controllable from the config file.
        if (isset($config['debug']) and $config['debug']) {
            echo 'Genesis debug mode enabled, to see debugging please use the pretty formatter.' . PHP_EOL . PHP_EOL;
            define('DEBUG_MODE', 1);
        }
    }

    /**
     * Setups configuration for current extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->
            children()->
                scalarNode('debug')->
                    defaultValue(false)->
                end()->
                arrayNode('connection_details')->
                    children()->
                        scalarNode('engine')->
                            defaultValue('pgsql')->
                        end()->
                        scalarNode('host')->
                            defaultValue('127.0.0.1')->
                        end()->
                        scalarNode('port')->
                            defaultValue('')->
                        end()->
                        scalarNode('schema')->
                            defaultValue(null)->
                        end()->
                        scalarNode('dbname')->
                            defaultValue(null)->
                        end()->
                        scalarNode('username')->
                            defaultValue('root')->
                        end()->
                        scalarNode('password')->
                            defaultValue(null)->
                        end()->
                        scalarNode('dbprefix')->
                            defaultValue(null)->
                        end()->
                        arrayNode('connection_options')->
                            ignoreExtraKeys(false)->
                        end()->
                    end()->
                end()->
                arrayNode('keywords')->
                    ignoreExtraKeys(false)->
                end()->
                arrayNode('notQuotableKeywords')->
                    ignoreExtraKeys(false)->
                end()->
            end()->
        end();

        return $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigKey()
    {
        return 'genesissql';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * Register additional compiler passes.
     */
    public function getCompilerPasses()
    {
        return array();
    }
}
