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
     * Get reference for a placeholder.
     *
     * @param string $placeholder The placeholder string.
     *
     * @return string|false Placeholder ref or false if the placeholder is not found.
     */
    public function getRefFromPlaceholder($placeholder);

    /**
     * Get single query for the external reference.
     *
     * @param string $externalRef The external ref enclosed in [].
     *
     * @return string
     */
    public function getSQLQueryForExternalReference($externalRef);

    /**
     * parseExternalQueryReferences.
     *
     * @param string $query
     *
     * @return string
     */
    public function parseExternalQueryReferences($query);

    /**
     * @param string $value The value to check.
     *
     * @return bool
     */
    public function isExternalReferencePlaceholder($value);

    /**
     * Check if the value provided is an external ref.
     *
     * @param string $value The value to check.
     *
     * @return bool
     */
    public function isExternalReference($value);

    /**
     * Prepends the prefix.
     *
     * @param string $prefix The prefix to prepend.
     * @param string $table The table to prefix.
     *
     * @return string
     */
    public function getPrefixedDatabaseName($prefix, $table);

    /**
     * Get table name from entity.
     *
     * @param string $entity The entity to extract the table name from.
     *
     * @return string
     */
    public function getTableName($entity);

    /**
     * Build up sql query from sql command object.
     *
     * @param Representations\SQLCommandInterface $sqlCommand The sql command.
     *
     * @return string
     */
    public function getQuery(Representations\SQLCommandInterface $sqlCommand);

    /**
     * Get the search condition operator for the columns provided.
     *
     * @param string $columns The columns to analyze.
     *
     * @return string
     */
    public function getSearchConditionOperatorForColumns($columns);

    /**
     * Check whether an and is supported by the columns.
     *
     * @param string $columns The columns to analyze.
     *
     * @return bool
     */
    public function isAndOperatorForColumns($columns);
}
