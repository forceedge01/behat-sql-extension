<?php

use Genesis\SQLExtension\Context\SQLContext;
use Genesis\SQLExtension\Tests\TestHelper;

/**
 * SQLContextTest class.
 */
class SQLContextTest extends TestHelper
{
    protected $dependencies;

    protected $testObject;

    public function setUp()
    {
        $this->dependencies = [
            'engine' => 'sqlite',
            'host' => 'localhost',
            'schema' => 'myschema',
            'dbname' => 'mydb',
            'username' => 'root',
            'password' => 'toor',
            'dbprefix' => 'prof_',
            'port' => '1126',
        ];

        $this->testObject = new SQLContext(
            $this->dependencies['engine'],
            $this->dependencies['host'],
            $this->dependencies['schema'],
            $this->dependencies['dbname'],
            $this->dependencies['username'],
            $this->dependencies['password'],
            $this->dependencies['dbprefix'],
            $this->dependencies['port']
        );
    }

    public function testGetSetParams()
    {
        $params = $this->testObject->get('dbManager')->getParams();

        self::assertEquals($this->dependencies['engine'], $params['DBENGINE']);
        self::assertEquals($this->dependencies['dbname'], $params['DBNAME']);
        self::assertEquals($this->dependencies['schema'], $params['DBSCHEMA']);
        self::assertEquals($this->dependencies['dbprefix'], $params['DBPREFIX']);
        self::assertEquals($this->dependencies['host'], $params['DBHOST']);
        self::assertEquals($this->dependencies['port'], $params['DBPORT']);
        self::assertEquals($this->dependencies['username'], $params['DBUSER']);
        self::assertEquals($this->dependencies['password'], $params['DBPASSWORD']);
        self::assertEquals([], $params['DBOPTIONS']);
    }
}
