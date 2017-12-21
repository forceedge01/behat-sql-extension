<?php

namespace Genesis\SQLExtension\Tests\Unit\Context;

use Genesis\SQLExtension\Context\Debugger;
use Genesis\SQLExtension\Tests\TestHelper;

/**
 * @group debugger
 * @group unit
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

        $expectedMessage = 'DEBUG >>> ' . $message . PHP_EOL;

        $this->assertInternalType('string', $result);
        $this->assertEquals($expectedMessage, $result);
    }
}
