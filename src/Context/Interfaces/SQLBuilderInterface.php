<?php

namespace Genesis\SQLExtension\Context\Interfaces;

use Behat\Gherkin\Node\TableNode;

interface SQLBuilderInterface
{
    /**
     * Will explode resulting in max 2 values.
     */
    const EXPLODE_MAX_LIMIT = 2;

    /**
     * Constructs a clause based on the glue, to be used for where and update clause.
     * 
     * @param string $commandType
     * @param string $glue
     * @param array $columns
     * 
     * @return string
     */
    public function constructSQLClause($commandType, $glue, array $columns);

    /**
     * Converts the incoming string param from steps to array.
     * 
     * @param string $columns
     * 
     * @return array
     */
    public function convertToArray($columns);

    /**
     * Quotes value if needed for sql.
     */
    public function quoteOrNot($val);

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToQueries(TableNode $node);

    /**
     * @param  TableNode $node The node with all fields and data.
     *
     * @return array The queries built of the TableNode.
     */
    public function convertTableNodeToSingleContextClause(TableNode $node);

    /**
     * returns sample data for a data type.
     *
     * @param string $type
     */
    public function sampleData($type);

    /**
     * Returns the columns stored after conversion to array.
     */
    public function getColumns();
}
