<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * pgsql class.
 */
class pgsql extends mysql
{
    /**
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbname, $host, $port = 5432)
    {
        return sprintf(
            'pgsql:dbname=%s;host=%s;port=%s',
            $dbname,
            $host,
            $port
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKeyForTable($database, $schema, $table)
    {
        if ($schema) {
            $table = $schema . '.' . $table;
        }

        if ($database) {
            $table = $database . '.' . $table;
        }

        $sql = sprintf(
            '
            SELECT a.attname
            FROM   pg_index i
            JOIN   pg_attribute a ON a.attrelid = i.indrelid
                                 AND a.attnum = ANY(i.indkey)
            WHERE  i.indrelid = \'%s\'::regclass
            AND    i.indisprimary;',
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

    protected function getRequiredTableColumnsQuery()
    {
        $query = parent::getRequiredTableColumnsQuery();

        return str_replace([
            parent::getLeftDelimiterForReservedWord(),
            parent::getRightDelimiterForReservedWord()
        ], [
            $this->getLeftDelimiterForReservedWord(),
            $this->getRightDelimiterForReservedWord()
        ], $query);
    }

    protected function getTableColumnsQuery()
    {
        $query = parent::getTableColumnsQuery();

        return str_replace([
            parent::getLeftDelimiterForReservedWord(),
            parent::getRightDelimiterForReservedWord()
        ], [
            $this->getLeftDelimiterForReservedWord(),
            $this->getRightDelimiterForReservedWord()
        ], $query);
    }

    /**
     * {@inheritDoc}
     */
    public function getLeftDelimiterForReservedWord()
    {
        return '"';
    }

    /**
     * {@inheritDoc}
     */
    public function getRightDelimiterForReservedWord()
    {
        return '"';
    }
}
