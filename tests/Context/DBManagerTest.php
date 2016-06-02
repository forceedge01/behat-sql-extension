<?php

namespace Genesis\SQLExtension\Context;

// Mock pdo class for testing.
class PDO
{
    private $dns;
    private $username;
    private $password;

    public function __construct($dns = null, $username = null, $password = null)
    {
        $this->dns = $dns;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDns()
    {
        return $this->dns;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }
}

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\DBManager;
use PHPUnit_Framework_TestCase;

class DBManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * The test object.
     */
    private $testObject;

    /**
     * Setup unit testing.
     */
    public function setup()
    {
        $params = [];

        $connection = new \Genesis\SQLExtension\Context\PDO();

        $this->testObject = new DBManager($params);
        $this->testObject->setConnection($connection);
    }

    /**
     * Test that the get params call works as expected.
     */
    public function testGetParams()
    {
        $result = $this->testObject->getParams();

        $this->assertTrue(is_array($result));
    }
}
