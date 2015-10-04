<?php

namespace Genesis\SQLExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Behat\MinkExtension;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Extension 
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class Extension extends MinkExtension\Extension
{
	public function load(array $config, ContainerBuilder $container)
	{
        if(isset($config['connection_details'])) {
        	DEFINE('SQLDBENGINE', $config['connection_details']['engine']);
	        DEFINE('SQLDBHOST', $config['connection_details']['host']);
	        DEFINE('SQLDBSCHEMA', $config['connection_details']['schema']);
	        DEFINE('SQLDBNAME', $config['connection_details']['dbname']);
	        DEFINE('SQLDBUSERNAME', $config['connection_details']['username']);
	        DEFINE('SQLDBPASSWORD', $config['connection_details']['password']);
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
        $config = $this->loadEnvironmentConfiguration();

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
            end()->
        end();

        parent::getConfig($builder);
    }
}