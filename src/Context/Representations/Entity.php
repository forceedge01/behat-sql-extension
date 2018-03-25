<?php

namespace Genesis\SQLExtension\Context\Representations;

/**
 * Entity class.
 */
class Entity extends Representation
{
    /**
     * @var string
     */
    private $rawInput;

    /**
     * @var string $tableName.
     */
    private $tableName;
    
    /**
     * @var string $databaseName.
     */
    private $databaseName;
    
    /**
     * @var string $schemaName.
     */
    private $schemaName;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var array
     */
    private $requiredColumns;

    /**
     * @param string $table
     * @param string $database
     * @param string $schema
     */
    public function __construct($rawInput, $table, $database = '', $schema = '')
    {
        $this->rawInput = $rawInput;
        $this->tableName = $table;
        $this->databaseName = $database;
        $this->schemaName = $schema;
    }

    /**
     * Get the rawInput.
     *
     * @return string
     */
    public function getRawInput()
    {
        return $this->rawInput;
    }
    
    /**
     * Set the rawInput.
     *
     * @param string $rawInput
     *
     * @return $this
     */
    public function setRawInput($rawInput)
    {
        $this->rawInput = $rawInput;
    
        return $this;
    }

    /**
     * Get the tableName.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }
    
    /**
     * Set the tableName.
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    
        return $this;
    }
    
    /**
     * Get the databaseName.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }
    
    /**
     * Set the databaseName.
     *
     * @param string $databaseName
     *
     * @return $this
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    
        return $this;
    }
    
    /**
     * Get the schemaName.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }
    
    /**
     * Set the schemaName.
     *
     * @param string $schemaName
     *
     * @return $this
     */
    public function setSchemaName($schemaName)
    {
        $this->schemaName = $schemaName;
    
        return $this;
    }

    /**
     * Get the primaryKey.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    
    /**
     * Set the primaryKey.
     *
     * @param string $primaryKey
     *
     * @return $this
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    
        return $this;
    }

    /**
     * Get the requiredColumns.
     *
     * @return array|null
     */
    public function getRequiredColumns()
    {
        return $this->requiredColumns;
    }
    
    /**
     * Set the requiredColumns.
     *
     * @param array $requiredColumns
     *
     * @return $this
     */
    public function setRequiredColumns($requiredColumns)
    {
        $this->requiredColumns = $requiredColumns;
    
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return (($this->getDatabaseName()) ? $this->getDatabaseName() . '.' : '') .
            (($this->getSchemaName()) ? $this->getSchemaName() . '.' : '') .
            $this->getTableName();
    }
}
