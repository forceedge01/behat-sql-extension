<?php

namespace Genesis\SQLExtension\Tests\Context;

use Genesis\SQLExtension\Extension;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionTest extends PHPUnit_Framework_TestCase
{
    private $extension;

    public function __construct()
    {
        $this->extension = new Extension();
    }

    public function testLoad()
    {
        // $containerBuilderMock = $this->getMockBuilder(ContainerBuilder::class)
        //     ->disableOriginalConstructor()
        //     ->getMock();

        // $config = [];

        // $this->extension->load($config, $containerBuilderMock);
    }

    public function testConfigure()
    {
        $arrayNodeDefinitionMock = $this->getMockBuilder(ArrayNodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scalarNodeMock = $this->getMockBuilder(ScalarNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeBuilderMock = $this->getMockBuilder(NodeBuilder::class)
            ->setMethods(['defaultValue', 'end'])
            ->getMock();

        $nodeBuilderMock->expects($this->any())
            ->method('scalarNode')
            ->will($this->returnValue($scalarNodeMock));

        $nodeBuilderMock->expects($this->any())
            ->method('end')
            ->will($this->returnSelf());

        $nodeBuilderMock->expects($this->any())
            ->method('defaultValue')
            ->with($this->logicalOr($this->isType('string'), null))
            ->will($this->returnSelf());

        $nodeBuilderMock->expects($this->any())
            ->method('ignoreExtraKeys')
            ->with(false)
            ->will($this->returnSelf());

        $arrayNodeDefinitionMock->expects($this->any())
            ->method('children')
            ->will($this->returnValue($nodeBuilderMock));

        $nodeBuilderMock->expects($this->any())
            ->method('arrayNode')
            ->will($this->returnValue($arrayNodeDefinitionMock));

        $scalarNodeMock->expects($this->any())
            ->method('defaultValue')
            ->with($this->logicalOr($this->isType('string'), null))
            ->will($this->returnValue($nodeBuilderMock));

        $this->extension->configure($arrayNodeDefinitionMock);
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
