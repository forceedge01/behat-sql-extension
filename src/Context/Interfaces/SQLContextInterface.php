<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;

interface SQLContextInterface
{
    public function iHaveAWhere($entity, $columns);

    public function iDontHaveAWhere($entity, $columns);

    public function iHaveAnExistingWithWhere($entity, $with, $columns);

    public function iHaveAnExistingWhere($entity, $where);

    public function iShouldHaveAWith($entity, $with);

    public function iShouldNotHaveAWith($entity, $with);

    public function convertTableNodeToQueries(TableNode $node);

    public function iSaveTheIdAs($key);

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
}
