<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * mysql class.
 */
class mysql extends BaseProvider
{
    /**
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbname, $host, $port = 3306)
    {
        return sprintf(
            'mysql:dbname=%s;host=%s;port=%s',
            $dbname,
            $host,
            $port
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getLeftDelimiterForReservedWord()
    {
        return '`';
    }

    /**
     * {@inheritDoc}
     */
    public function getRightDelimiterForReservedWord()
    {
        return '`';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKeyForTable($database, $schema, $table)
    {
        $sql = sprintf(
            '
            SELECT `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = "%s")
            AND (`TABLE_NAME` = "%s")
            AND (`COLUMN_KEY` = "PRI")',
            $database,
            $table
        );

        $statement = $this->getExecutor()->execute($sql);
        $result = $statement->fetchAll();
        $this->getExecutor()->closeStatement($statement);

        if (! $result) {
            return false;
        }

        return $result[0][0];
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredTableColumns($database, $schema, $table)
    {
        $resetSchema = false;
        // If the DBSCHEMA is not set, try using the database name if provided with the table.
        // If this happens the schema generation is dynamic so keep resetting the stored schema.
        if (! $schema) {
            $resetSchema = true;
            preg_match('/(.*)\./', $table, $db);

            if (isset($db[1])) {
                $schema = $db[1];
            }
        }

        // Parse out the table name.
        $table = preg_replace('/(.*\.)/', '', $table);
        $table = trim($table, '`');

        // Statement to extract all required columns for a table.
        $sqlStatement = "
            SELECT 
                `column_name`, `data_type` 
            FROM 
                information_schema.columns 
            WHERE 
                is_nullable = 'NO'
            AND 
                table_name = '%s'
            AND 
                table_schema = '%s';";

        // Get not null columns
        $sql = sprintf(
            $sqlStatement,
            $table,
            $schema
        );

        // Reset schema after the fields have been extracted.
        if ($resetSchema) {
            $schema = null;
        }

        $statement = $this->getExecutor()->execute($sql);
        $result = $statement->fetchAll();
        $this->getExecutor()->closeStatement($statement);

        $cols = [];
        if ($result) {
            foreach ($result as $column) {
                $cols[$column['column_name']] = [
                    'type' => $column['data_type'],
                    'length' => 5000
                ];
            }
        }

        return $cols;
    }
}
