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

/**
 * Simulates a database for testing purposes.
 *
 * This class mimics the behavior of `Database` without requiring an actual
 * database connection. It allows tests to define expected SQL queries, their
 * parameter bindings, and the results to be returned.
 *
 * #### Example
 * ```php
 * use \Harmonia\Systems\DatabaseSystem\Fakes\FakeDatabase;
 * use \Harmonia\Systems\DatabaseSystem\Database;
 * use \Peneus\Model\Account;
 *
 * class AccountTest extends \PHPUnit\Framework\TestCase
 * {
 *     function testIsRegistered()
 *     {
 *         $fakeDatabase = new FakeDatabase();
 *         $fakeDatabase->Expect(
 *             sql: 'SELECT COUNT(*) FROM account WHERE email = :email',
 *             bindings: ['email' => 'john@example.com'],
 *             result: [[1]]
 *         );
 *         Database::ReplaceInstance($fakeDatabase);
 *
 *         $count = Account::Count(
 *             condition: 'email = :email',
 *             bindings: ['email' => 'john@example.com']
 *         );
 *         $this->assertSame(1, $count);
 *     }
 * }
 * ```
 */
class FakeDatabase extends Database
{
    private const UNLIMITED = null;

    private array $expectations;
    private int $lastInsertId;
    private int $lastAffectedRowCount;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * This constructor overrides the protected base constructor inherited from
     * the singleton, allowing the class to be instantiated with `new` in test
     * code.
     */
    public function __construct()
    {
        $this->expectations = [];
        $this->lastInsertId = 0;
        $this->lastAffectedRowCount = 0;
    }

    /**
     * Defines a query expectation.
     *
     * This method registers an expected SQL query with its associated parameter
     * bindings and return behavior. When `Execute` is later called with a
     * matching query and bindings, the configured result will be returned.
     *
     * @param string $sql
     *   The SQL query string to match against during execution. Placeholders
     *   (e.g., `:email`) must be used instead of literal values, and must
     *   correspond to the binding keys.
     * @param array<string, mixed> $bindings
     *   The expected parameter bindings for the query. Keys must match the SQL
     *   placeholders without the colon (e.g., `'email' => 'john@example.com'`
     *   for `:email` in SQL).
     * @param ?array<int, array<string, mixed>> $result
     *   (Optional) The result rows to return from the query. Each row must be
     *   an associative array with column names as keys. If `null`, the query
     *   will return `null` to simulate failure. Defaults to an empty array.
     * @param int $lastInsertId
     *   (Optional) The value to be returned from `LastInsertId` after the
     *   query is executed. Defaults to `0`, simulating no last insert ID.
     * @param int $lastAffectedRowCount
     *   (Optional) The value to be returned from `LastAffectedRowCount` after
     *   the query is executed. Use `-1` to simulate a query failure. Defaults
     *   to `0`, indicating no rows were affected.
     * @param ?int $times
     *   (Optional) The number of times this expectation may be matched. If set
     *   to a positive integer, the expectation will only match that many times;
     *   exceeding it will throw an exception. If `null`, the expectation may be
     *   matched unlimited times.
     * @return void
     */
    public function Expect(
        string $sql,
        array $bindings = [],
        ?array $result = [],
        int $lastInsertId = 0,
        int $lastAffectedRowCount = 0,
        ?int $times = self::UNLIMITED
    ): void
    {
        $key = $this->keyFor($sql, $bindings);
        $this->expectations[$key] = [
            'result' => $result,
            'lastInsertId' => $lastInsertId,
            'lastAffectedRowCount' => $lastAffectedRowCount,
            'times' => $times,
        ];
    }

    /**
     * Simulates query execution and returns a configured result.
     *
     * This method matches the given query and bindings against expectations
     * registered via `Expect`. If a match is found, it returns a `FakeResultSet`
     * or `null` (if configured). If no matching expectation exists, or if the
     * expectation has already been used the maximum number of allowed times, an
     * exception is thrown.
     *
     * @param Query $query
     *   The query object containing the SQL and its bound parameters.
     * @return ?ResultSet
     *   A simulated result set based on the configured expectation, or `null`
     *   if the expectation result was explicitly set to `null`.
     * @throws \RuntimeException
     *   If no matching expectation is found, or if the expectation has been
     *   exhausted.
     */
    public function Execute(Query $query): ?ResultSet
    {
        $sql = $query->ToSql();
        $bindings = $query->Bindings();
        $key = $this->keyFor($sql, $bindings);
        if (!\array_key_exists($key, $this->expectations)) {
            throw new \RuntimeException(
                "Unexpected {$this->formatExpectation($sql, $bindings)}");
        }
        $expectation = &$this->expectations[$key];
        if ($expectation['times'] !== self::UNLIMITED) {
            if ($expectation['times'] <= 0) {
                throw new \RuntimeException(
                    "Exhausted {$this->formatExpectation($sql, $bindings)}");
            }
            --$expectation['times'];
        }
        $this->lastInsertId = $expectation['lastInsertId'];
        $this->lastAffectedRowCount = $expectation['lastAffectedRowCount'];
        if ($expectation['result'] === null) {
            return null;
        }
        return new FakeResultSet($expectation['result']);
    }

    /**
     * Returns the simulated ID generated by the last `INSERT` query.
     *
     * @return int
     *   The last insert ID. Defaults to `0` if not explicitly configured.
     */
    public function LastInsertId(): int
    {
        return $this->lastInsertId;
    }

    /**
     * Returns the simulated number of affected rows by the last `UPDATE` or
     * `DELETE` query.
     *
     * @return int
     *   The number of affected rows. Defaults to `0`, indicating that no rows
     *   were affected. Use `-1` to simulate a failed query.
     */
    public function LastAffectedRowCount(): int
    {
        return $this->lastAffectedRowCount;
    }

    /**
     * Simulates a database transaction block.
     *
     * The callback is invoked immediately without starting a real transaction.
     *
     * @param callable $callback
     *   The operation to simulate within a transaction block.
     * @return mixed
     *   The return value of the callback, or `false` if an exception was thrown.
     */
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

    /**
     * Computes a stable hash key for matching a query expectation.
     *
     * The key is derived from the SQL string and the sorted parameter bindings
     * to ensure consistent matching.
     *
     * @param string $sql
     *   The SQL query string with placeholders.
     * @param array<string, mixed> $bindings
     *   The parameter bindings associated with the query.
     * @return string
     *   A SHA-256 hash representing the expectation signature.
     */
    private function keyFor(string $sql, array $bindings): string
    {
        \ksort($bindings);
        return \hash('sha256', $sql . "\0" . \json_encode($bindings));
    }

    /**
     * Formats a query and its bindings for display in exception messages.
     *
     * @param string $sql
     *   The SQL query string with placeholders.
     * @param array<string, mixed> $bindings
     *   The parameter bindings associated with the query.
     * @return string
     *   A single-line formatted string combining the query and bindings.
     */
    private function formatExpectation(string $sql, array $bindings): string
    {
        return "query: `{$sql}`, bindings: " . \json_encode($bindings);
    }

    #endregion private
}
