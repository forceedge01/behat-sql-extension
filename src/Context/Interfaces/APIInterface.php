<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;

interface APIInterface
{
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
     * User friendly version of iDontHaveAWhere.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function delete($table, $where);

    /**
     * User friendly version of iHaveAnExistingWithWhere.
     *
     * @param string $table The table to delete from.
     * @param string $update The columns to update.
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

    /**
     * Assert if record exists.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function assertExists($table, $where);

    /**
     * Assert if record does not exist.
     *
     * @param string $table The table to delete from.
     * @param string $where The where clause.
     *
     * @return string
     */
    public function assertNotExists($table, $where);
}
