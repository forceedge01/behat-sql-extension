<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;

interface SQLContextInterface
{
    /**
     * @param string $entity.
     * @param string $columns.
     */
    public function iHaveAWhere($entity, $columns);

    /**
     * @param string $entity.
     * @param string $columns.
     */
    public function iDontHaveAWhere($entity, $columns);

    /**
     * @param string $entity.
     * @param string $with.
     * @param string $columns.
     */
    public function iHaveAnExistingWithWhere($entity, $with, $columns);

    /**
     * @param string $entity.
     * @param string $where.
     */
    public function iHaveAnExistingWhere($entity, $where);

    /**
     * @param string $entity.
     * @param string $with.
     */
    public function iShouldHaveAWith($entity, $with);

    /**
     * @param string $entity.
     * @param string $with.
     */
    public function iShouldNotHaveAWith($entity, $with);

    /**
     * @param TableNode $node.
     */
    public function convertTableNodeToQueries(TableNode $node);

    /**
     * @param string $key.
     */
    public function iSaveTheIdAs($key);

    /**
     * Enable debug mode.
     */
    public function iAmInDebugMode();

    /**
     * Get a dependency.
     *
     * @param string $dependency.
     *
     * @return object
     */
    public function get($dependency);
}
