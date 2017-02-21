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
     * User friendly version of iDontHaveAWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function delete($table, $where);

    /**
     * User friendly version of iHaveAWith.
     *
     * @param $table The table to insert into.
     * @param $values Values to insert.
     *
     * @return string
     */
    public function insert($table, $values);

    /**
     * User friendly version of iHaveAnExistingWithWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function update($table, $update, $where);

    /**
     * User friendly version of iHaveAnExistingWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function select($table, $where);

    /**
     * Get a dependency.
     *
     * @param string $dependency.
     *
     * @return object
     */
    public function get($dependency);
}
