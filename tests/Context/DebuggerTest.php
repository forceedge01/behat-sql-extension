<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Context\Debugger;

/**
 * @group debugger
 */
class DebuggerTest extends TestHelper
{
    /**
     * Test that the log.
     */
    public function testLog()
    {
        define('DEBUG_MODE', 1);

        $message = 'This is just a test';

        ob_start();
        Debugger::log($message);
        $result = ob_get_clean();

        $expectedMessage = 'DEBUG >>> ' . $message . PHP_EOL . PHP_EOL;

        $this->assertInternalType('string', $result);
        $this->assertEquals($expectedMessage, $result);
    }
}
