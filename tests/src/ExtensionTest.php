<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Extension;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\ScalarNode;

class ExtensionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Extension
     */
    private $extension;

    protected function setUp()
    {
        $this->extension = new Extension();
    }

    public function testConfigure()
    {
        /** @var ArrayNodeDefinition|\PHPUnit_Framework_MockObject_MockObject $arrayNodeDefinitionMock1 */
        $arrayNodeDefinitionMock1 = $this->getMockBuilder(ArrayNodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $arrayNodeDefinitionMock2 = $this->getMockBuilder(ArrayNodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeBuilderMock1 = $this->getMockBuilder(NodeBuilder::class)
            ->getMock();

        $nodeBuilderMock2 = $this->getMockBuilder(NodeBuilder::class)
            ->getMock();

        $scalarNodeDefinitionMock = $this->getMockBuilder(ScalarNodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $arrayNodeDefinitionMock1->expects($this->any())
            ->method('children')
            ->will($this->returnValue($nodeBuilderMock1));

        $nodeBuilderMock1->expects($this->any())
            ->method('arrayNode')
            ->will($this->returnValue($arrayNodeDefinitionMock2));

        $arrayNodeDefinitionMock2->expects($this->any())
            ->method('children')
            ->will($this->returnValue($nodeBuilderMock2));

        $nodeBuilderMock2->expects($this->any())
            ->method('scalarNode')
            ->will($this->returnValue($scalarNodeDefinitionMock));

        $scalarNodeDefinitionMock->expects($this->any())
            ->method('defaultValue')
            ->with($this->logicalOr($this->isType('string'), null))
            ->will($this->returnSelf());

        $scalarNodeDefinitionMock->expects($this->any())
            ->method('end')
            ->will($this->returnValue($nodeBuilderMock2));

        $nodeBuilderMock2->expects($this->any())
            ->method('end')
            ->will($this->returnValue($arrayNodeDefinitionMock2));

        $arrayNodeDefinitionMock2->expects($this->any())
            ->method('end')
            ->will($this->returnValue($nodeBuilderMock1));

        $arrayNodeDefinitionMock2->expects($this->any())
            ->method('ignoreExtraKeys')
            ->with(false)
            ->will($this->returnSelf());

        $nodeBuilderMock1->expects($this->any())
            ->method('end')
            ->will($this->returnValue($arrayNodeDefinitionMock1));

        $this->extension->configure($arrayNodeDefinitionMock1);
    }

    /**
     * getConfigKey Test that getConfigKey executes as expected.
     */
    public function testGetConfigKey()
    {
        $result = $this->extension->getConfigKey();

        $this->assertEquals('genesissql', $result);
    }

    /**
     * testGetCompilerPasses Test that getCompilerPasses executes as expected.
     */
    public function testGetCompilerPasses()
    {
        // Execute
        $result = $this->extension->getCompilerPasses();

        // Assert Result
        $this->assertEquals([], $result);
    }
}
