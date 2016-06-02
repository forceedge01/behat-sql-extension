<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\LocalKeyStore;
use PHPUnit_Framework_TestCase;

class KeyStoreTest extends PHPUnit_Framework_TestCase
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
        $this->testObject = new LocalKeyStore();
    }

    /**
     * testSetKeyword Test that setKeyword executes as expected.
     */
    public function testSetAndGetKeyword()
    {
        $keyword = 'key';
        $value = 'this should be saved and retrieved';

        // Execute.
        $this->testObject->setKeyword($keyword, $value);

        // Get the keyword out.
        $result = $this->testObject->getKeyword($keyword);

        // Assert Result.
        $this->assertEquals($value, $result);
        $this->assertEquals($value, $_SESSION['behat']['GenesisSqlExtension']['keywords'][$keyword]);
    }
}
