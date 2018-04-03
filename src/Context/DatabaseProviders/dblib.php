<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * dblib class.
 */
class dblib extends BaseProvider
{
    /**
     * @var array
     */
    private static $primaryKeys = [];

    /**
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbname, $host, $port = null)
    {
        $portDns = '';
        if ($port) {
            $portDns .= ':' . $port;
        }

        return "dblib:host=$host$portDns;dbname=$dbname";
    }

    /**
     * {@inheritDoc}
     */
    public function getLeftDelimiterForReservedWord()
    {
        return '[';
    }

    /**
     * {@inheritDoc}
     */
    public function getRightDelimiterForReservedWord()
    {
        return ']';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKeyForTable($database, $schema, $table)
    {
        $database = $this->normaliseMsSQLPotentialKeyword($database);
        $schema = $this->normaliseMsSQLPotentialKeyword($schema);
        $table = $this->normaliseMsSQLPotentialKeyword($table);

        $key = $database . $schema . $table;

        if (isset(self::$primaryKeys[$key])) {
            return self::$primaryKeys[$key];
        }

        $additionalWhereClause = '';
        if ($database) {
            $additionalWhereClause = " AND TC.TABLE_CATALOG = '$database'";
        }

        if ($schema) {
            $additionalWhereClause .= " AND TC.TABLE_SCHEMA = '$schema'";
        }

        $sql = "
            SELECT KU.table_name as TABLENAME,column_name as PRIMARYKEYCOLUMN
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC
            INNER JOIN
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU
                      ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND
                         TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND 
                         KU.table_name='$table' $additionalWhereClause;";

        $statement = $this->getExecutor()->execute($sql);
        $result = $statement->fetchAll();

        self::$primaryKeys[$key] = false;
        if (isset($result[0]['PRIMARYKEYCOLUMN'])) {
            self::$primaryKeys[$key] = $result[0]['PRIMARYKEYCOLUMN'];
        }

        return self::$primaryKeys[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredTableColumns($database, $schema, $table)
    {
        $database = $this->normaliseMsSQLPotentialKeyword($database);
        $schema = $this->normaliseMsSQLPotentialKeyword($schema);
        $table = $this->normaliseMsSQLPotentialKeyword($table);

        $additionalWhereClause = '';
        if ($database) {
            $additionalWhereClause = " AND TABLE_CATALOG = '$database'";
        }

        if ($schema) {
            $additionalWhereClause .= " AND TABLE_SCHEMA = '$schema'";
        }

        $sql = "
            SELECT
                COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
            FROM
                information_schema.columns TC
            WHERE
                TABLE_NAME = '$table'
            AND
                IS_NULLABLE = 'NO'
            AND
                Column_DEFAULT IS null {$additionalWhereClause};";

        $statement = $this->getExecutor()->execute($sql);
        $result = $statement->fetchAll();
        $this->getExecutor()->closeStatement($statement);

        $columns = [];
        if ($result) {
            foreach ($result as $value) {
                $columns[$value['COLUMN_NAME']] = [
                    'type' => $value['DATA_TYPE'],
                    'length' => $value['CHARACTER_MAXIMUM_LENGTH']
                ];
            }
        }

        return $columns;
    }

    /**
     * @param string $keyword
     *
     * @return string
     */
    private function normaliseMsSQLPotentialKeyword($keyword)
    {
        return str_replace(['[', ']'], '', $keyword);
    }
}
