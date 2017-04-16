<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;

interface APIInterface
{
    /**
     * User friendly version of iHaveAWith.
     *
     * @param string $table The table to insert into.
     * @param array $values Values to insert.
     *
     * @return string
     */
    public function insert($table, array $values);

    /**
     * User friendly version of iDontHaveAWhere.
     *
     * @param string $table The table to delete from.
     * @param array $where The where clause.
     *
     * @return string
     */
    public function delete($table, array $where);

    /**
     * User friendly version of iHaveAnExistingWithWhere.
     *
     * @param string $table The table to delete from.
     * @param array $update The columns to update.
     * @param array $where The where clause.
     *
     * @return string
     */
    public function update($table, array $update, array $where);

    /**
     * User friendly version of iHaveAnExistingWhere.
     *
     * @param string $table The table to delete from.
     * @param array $where The where clause.
     *
     * @return string
     */
    public function select($table, array $where);

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
     * @param array $where The where clause.
     *
     * @return string
     */
    public function assertExists($table, array $where);

    /**
     * Assert if record does not exist.
     *
     * @param string $table The table to delete from.
     * @param array $where The where clause.
     *
     * @return string
     */
    public function assertNotExists($table, array $where);
}
