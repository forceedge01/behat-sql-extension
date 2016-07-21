<?php

namespace Genesis\SQLExtension\Context;

use Exception;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL Handler.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
class SQLHistory implements Interfaces\SQLHistoryInterface
{
    /**
     * Holds the history of commands executed.
     */
    private $history;

    /**
     * Setup object.
     */
    public function __construct()
    {
        $this->resetHistory();
    }

    /**
     * Returns history of commands executed.
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Resets/Empties the history.
     *
     * @return this
     */
    public function resetHistory()
    {
        $this->history = [
            'select' => [],
            'insert' => [],
            'delete' => [],
            'update' => []
        ];

        return $this;
    }

    /**
     * @param string $commandType The command type.
     * @param string $table The table on which the operation was performed.
     * @param string $sql The sql executed.
     * @param int|null $id The last id.
     *
     * @return $this
     */
    public function addToHistory($commandType, $table, $sql, $id = null)
    {
        if (! array_key_exists($commandType, $this->history)) {
            throw new Exception(
                'Invalid command type given "' . $commandType . '", allowed types are: ' .
                print_r(array_keys($this->history), true)
            );
        }

        $history = Representations\History::instance()
            ->setEntity($table)
            ->setSql($sql)
            ->setLastId($id);

        $this->history[$commandType][] = $history;

        return $this;
    }
}
