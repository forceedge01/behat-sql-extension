<?php

namespace Genesis\SQLExtension;

use Behat\MinkExtension;
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
class Extension extends MinkExtension\Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        if (isset($config['connection_details'])) {
            DEFINE('SQLDBENGINE', $config['connection_details']['engine']);
            DEFINE('SQLDBHOST', $config['connection_details']['host']);
            DEFINE('SQLDBSCHEMA', $config['connection_details']['schema']);
            DEFINE('SQLDBNAME', $config['connection_details']['dbname']);
            DEFINE('SQLDBUSERNAME', $config['connection_details']['username']);
            DEFINE('SQLDBPASSWORD', $config['connection_details']['password']);
            session_start();
            // Store any keywords set in behat.yml file
            if (isset($config['keywords']) and $config['keywords']) {
                foreach ($config['keywords'] as $keyword => $value) {
                    $_SESSION['behat']['GenesisSqlExtension']['keywords'][$keyword] = $value;
                }
            }

            // Set 'notQuotableKeywords' for later use.
            $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = [];

            if (isset($config['notQuotableKeywords'])) {
                $_SESSION['behat']['GenesisSqlExtension']['notQuotableKeywords'] = $config['notQuotableKeywords'];
            }
        }

        parent::load($config, $container);
    }

    /**
     * Setups configuration for current extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $this->loadEnvironmentConfiguration();

        $builder->
            children()->
                arrayNode('connection_details')->
                    children()->
                        scalarNode('engine')->
                            defaultValue('pgsql')->
                        end()->
                        scalarNode('host')->
                            defaultValue('127.0.0.1')->
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
                    end()->
                end()->
                arrayNode('keywords')->
                    ignoreExtraKeys(false)->
                end()->
            end()->
        end();

        parent::getConfig($builder);
    }
}
