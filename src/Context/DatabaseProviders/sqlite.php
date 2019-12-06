<?php

namespace Genesis\SQLExtension\Context\DatabaseProviders;

/**
 * sqlite class.
 */
class sqlite extends BaseProvider
{
    /**
     * @param string $dbpath The absolute path or :memory for in memory db.
     *
     * {@inheritDoc}
     */
    public function getPdoDnsString($dbpath, $host = null, $port = null)
    {
        return "sqlite:{$dbpath}";
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
        $query = 'PRAGMA table_info(' . $table . ')';
        $statement = $this->getExecutor()->execute($query);

        foreach ($statement->fetchAll() as $column) {
            if ($column['pk'] == 1) {
                return $column['name'];
            }
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredTableColumns($database, $schema, $table)
    {
        $query = 'PRAGMA table_info(' . $table . ')';
        $statement = $this->getExecutor()->execute($query);
        $requiredColumns = [];

        foreach ($statement->fetchAll() as $column) {
            if ($column['notnull'] == 1) {
                $type = explode('(', $column['type']);
                $requiredColumns[$column['name']] = [
                    'type' => $type[0],
                    'length' => isset($type[1]) ? (int) trim($type[1], '()') : 5000
                ];
            }
        }

        return $requiredColumns;
    }
}
