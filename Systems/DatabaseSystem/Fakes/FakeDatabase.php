<?php declare(strict_types=1);
/**
 * FakeDatabase.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\DatabaseSystem\Fakes;

use \Harmonia\Systems\DatabaseSystem\Database;

use \Harmonia\Systems\DatabaseSystem\Queries\Query;
use \Harmonia\Systems\DatabaseSystem\ResultSet;

class FakeDatabase extends Database
{
    private const UNLIMITED = null;

    private array $expectations;
    private int $lastInsertId;
    private int $lastAffectedRowCount;

    #region public -------------------------------------------------------------

    public function __construct()
    {
        $this->expectations = [];
        $this->lastInsertId = 0;
        $this->lastAffectedRowCount = -1;
    }

    public function Expect(
        string $sql,
        array $bindings = [],
        array $result = [],
        int $lastInsertId = 0,
        int $lastAffectedRowCount = -1,
        ?int $times = self::UNLIMITED
    ): void
    {
        $key = $this->key($sql, $bindings);
        $this->expectations[$key] = [
            'result' => $result,
            'lastInsertId' => $lastInsertId,
            'lastAffectedRowCount' => $lastAffectedRowCount,
            'times' => $times,
        ];
    }

    public function Execute(Query $query): ?ResultSet
    {
        $sql = $query->ToSql();
        $bindings = $query->Bindings();
        $key = $this->key($sql, $bindings);
        if (!\array_key_exists($key, $this->expectations)) {
            throw new \RuntimeException("Unexpected query: {$sql}");
        }
        $expectation = &$this->expectations[$key];
        if ($expectation['times'] !== self::UNLIMITED) {
            if ($expectation['times'] <= 0) {
                throw new \RuntimeException("Expectation exhausted: {$sql}");
            }
            --$expectation['times'];
        }
        $this->lastInsertId = $expectation['lastInsertId'];
        $this->lastAffectedRowCount = $expectation['lastAffectedRowCount'];
        return new FakeResultSet($expectation['result']);
    }

    public function LastInsertId(): int
    {
        return $this->lastInsertId;
    }

    public function LastAffectedRowCount(): int
    {
        return $this->lastAffectedRowCount;
    }

    public function WithTransaction(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return false;
        }
    }

    #endregion public

    #region private ------------------------------------------------------------

    private function key(string $sql, array $bindings): string
    {
        \ksort($bindings);
        return \hash('sha256', $sql . "\0" . \json_encode($bindings));
    }

    #endregion private
}
