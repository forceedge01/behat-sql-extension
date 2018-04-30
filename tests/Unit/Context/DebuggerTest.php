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
    public function testLogModeAll()
    {
        Debugger::enable(Debugger::MODE_ALL);

        $message = 'This is just a test';

        ob_start();
        Debugger::log($message);
        $result = ob_get_clean();

        $expectedMessage = 'DEBUG >>> ' . $message . PHP_EOL;

        $this->assertInternalType('string', $result);
        $this->assertEquals($expectedMessage, $result);
    }

    /**
     * Test that the log.
     */
    public function testLogModeSqlOnly()
    {
        Debugger::enable(Debugger::MODE_SQL_ONLY);

        $message = 'This is just a test';
        $message2 = 'SQL execution';

        ob_start();
        Debugger::log($message);
        Debugger::log($message2);
        $result = ob_get_clean();

        $expectedMessage = 'DEBUG >>> ' . $message2 . PHP_EOL;

        $this->assertInternalType('string', $result);
        $this->assertEquals($expectedMessage, $result);
    }
}
