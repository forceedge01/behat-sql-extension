<?php

namespace Genesis\SQLExtension\Context\Interfaces;

/*
 * This file is part of the Behat\SQLExtension
 *
 * (c) Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * SQL History.
 *
 * @author Abdul Wahab Qureshi <its.inevitable@hotmail.com>
 */
interface SQLHistoryInterface
{
    /**
     * Returns history of commands executed.
     *
     * @return array
     */
    public function getHistory();

    /**
     * Resets/Empties the history.
     *
     * @return this
     */
    public function resetHistory();

    /**
     * @param string $commandType The command type.
     * @param string $sql The sql executed.
     * @param int|null $id The last id.
     *
     * @return $this
     */
    public function addToHistory($commandType, $sql, $id = null);
}
