<?php declare(strict_types=1);
/**
 * Connection.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\DatabaseSystem;

use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiHandle;
use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiResult;
use \Harmonia\Systems\DatabaseSystem\Proxies\MySQLiStatement;
use \Harmonia\Systems\DatabaseSystem\Queries\Query;

/**
 * Represents a connection to a MySQL server.
 */
class Connection
{
    /**
     * Handle to the MySQL connection.
     *
     * @var ?MySQLiHandle
     */
    private ?MySQLiHandle $handle = null;

    #region public -------------------------------------------------------------

    /**
     * Opens a connection to a MySQL server.
     *
     * @param string $host
     *   The host name or IP address of the MySQL server.
     * @param string $username
     *   The username for the MySQL authentication.
     * @param string $password
     *   The password for the MySQL authentication.
     * @param ?string $charset
     *   (Optional) The character set to use for the connection.
     * @throws \RuntimeException
     *   If the connection to the MySQL server fails or if the character set
     *   cannot be set.
     */
    public function __construct(string $host, string $username, string $password,
        ?string $charset = null)
    {
        $handle = $this->createHandle($host, $username, $password);
        if ($charset !== null) {
            try {
                $this->setCharset($handle, $charset);
            } catch (\RuntimeException $e) {
                $handle->close();
                throw $e;
            }
        }
        $this->handle = $handle;
    }

    /**
     * Closes the connection to the MySQL server.
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            $this->handle->close();
            $this->handle = null;
        }
    }

    /**
     * Selects a database as the current database for the subsequent queries.
     *
     * @param string $databaseName
     *   The name of the database to select.
     * @throws \RuntimeException
     *   If the database selection fails.
     */
    public function SelectDatabase(string $databaseName): void
    {
        try {
            if (!$this->handle->select_db($databaseName)) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Executes a query on the MySQL server.
     *
     * This method prepares and executes the given query. If the query produces
     * a result set (such as `SELECT`, `SHOW`, `DESCRIBE`, or `EXPLAIN`), it
     * returns a `MySQLiResult` object. For queries that do not produce a result
     * set (such as `INSERT`, `UPDATE`, or `DELETE`), it returns `null`. Any
     * execution failure results in an exception.
     *
     * @param Query $query
     *   The query object containing SQL and optionally its bindings.
     * @return ?MySQLiResult
     *   A `MySQLiResult` if the query produces a result set, or `null` if the
     *   query does not produce a result set.
     * @throws \RuntimeException
     *   If the query preparation or execution fails.
     */
    public function Execute(Query $query): ?MySQLiResult
    {
        $transformedQuery = self::transformQuery($query);
        if (PHP_VERSION_ID < 80200)
        {
            $statement = $this->prepareStatement($transformedQuery->sql);
            try {
                $this->executeStatement($statement, $transformedQuery->values);
            } catch (\RuntimeException $e) {
                $statement->close();
                throw $e;
            }
            try {
                $result = $this->getStatementResult($statement);
            } catch (\RuntimeException $e) {
                $statement->close();
                throw $e;
            }
            $statement->close();
        }
        else // PHP >= 8.2.0
        {
            $result = $this->executeQuery(
                $transformedQuery->sql,
                $transformedQuery->values
            );
        }
        return $result;
    }

    /**
     * Retrieves the ID generated by the last `INSERT` query.
     *
     * @return int
     *   The auto-generated ID from the last successful `INSERT` query. If the
     *   last query was not an `INSERT` or no `AUTO_INCREMENT` value was
     *   generated, this method returns `0`.
     */
    public function LastInsertId(): int
    {
        return $this->handle->insert_id;
    }

    /**
     * Retrieves the number of rows affected by the last `INSERT`, `UPDATE`,
     * `REPLACE`, or `DELETE` query.
     *
     * @return int
     *   The number of rows affected by the last modifying query. Returns `0`
     *   if no rows were affected. Returns `-1` if the query failed.
     */
    public function LastAffectedRowCount(): int
    {
        return $this->handle->affected_rows;
    }

    /**
     * Initiates a transaction.
     *
     * @throws \RuntimeException
     *   If the transaction initiation fails.
     */
    public function BeginTransaction(): void
    {
        try {
            if (!$this->handle->begin_transaction()) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Commits the current transaction.
     *
     * @throws \RuntimeException
     *   If the transaction commit fails.
     */
    public function CommitTransaction(): void
    {
        try {
            if (!$this->handle->commit()) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Rolls back the current transaction.
     *
     * @throws \RuntimeException
     *   If the transaction rollback fails.
     */
    public function RollbackTransaction(): void
    {
        try {
            if (!$this->handle->rollback()) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Escapes special characters in a string for use in SQL statements.
     *
     * This method is intended for use in SQL queries where parameter binding
     * is not supported by the database engine (e.g., `SHOW DATABASES LIKE`).
     * It helps prevent SQL injection by escaping input values.
     *
     * The returned value is not quoted automatically. It is the caller's
     * responsibility to wrap the result in single quotes when embedding it
     * directly in a SQL string.
     *
     * #### Example
     * ```php
     * $sql = "SHOW DATABASES LIKE '{$connection->EscapeString($name)}'";
     * ```
     *
     * @param string $string
     *   The string to be escaped.
     * @return string
     *   The escaped string, safe for use in SQL contexts when properly quoted.
     */
    public function EscapeString(string $string): string
    {
        return $this->handle->real_escape_string($string);
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _new_mysqli(string $host, string $username,
        string $password): MySQLiHandle
    {
        $mysqli = @new \mysqli($host, $username, $password);
        return new MySQLiHandle($mysqli);
    }

    protected function createHandle(string $host, string $username,
        string $password): MySQLiHandle
    {
        try {
            $handle = $this->_new_mysqli($host, $username, $password);
            if ($handle->connect_errno !== 0) {
                throw new \RuntimeException($handle->connect_error,
                                            $handle->connect_errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
        return $handle;
    }

    protected function setCharset(MySQLiHandle $handle, string $charset): void
    {
        try {
            if (!$handle->set_charset($charset)) {
                throw new \RuntimeException($handle->error, $handle->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
    }

    #region PHP < 8.2.0 --------------------------------------------------------

    protected function prepareStatement(string $sql): MySQLiStatement
    {
        try {
            $statement = $this->handle->prepare($sql);
            if (!$statement) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
        return $statement;
    }

    protected function executeStatement(MySQLiStatement $statement,
        array $values): void
    {
        try {
            if (!$statement->execute($values)) {
                throw new \RuntimeException($statement->error,
                                            $statement->errno);
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
    }

    protected function getStatementResult(MySQLiStatement $statement): ?MySQLiResult
    {
        try {
            $result = $statement->get_result();
            if ($result === false) {
                if ($statement->errno !== 0) {
                    throw new \RuntimeException($statement->error,
                                                $statement->errno);
                }
                $result = null; // empty result
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
        return $result;
    }

    #endregion PHP < 8.2.0

    #region PHP >= 8.2.0 -------------------------------------------------------

    protected function executeQuery(string $sql, array $values): ?MySQLiResult
    {
        try {
            $result = $this->handle->execute_query($sql, $values);
            if ($result === false) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
            if ($result === true) {
                $result = null; // empty result
            }
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
        return $result;
    }

    #endregion PHP >= 8.2.0

    #endregion protected

    #region private ------------------------------------------------------------

    /**
     * Converts a query with named placeholders into MySQLi-compatible format.
     *
     * This method replaces named placeholders (e.g., `:id`, `:name`) with `?`
     * for MySQLi `prepare()`, reorders binding values to match their appearance
     * in the query.
     *
     * @param Query $query
     *   The query object containing SQL and its bindings.
     * @return \stdClass
     *   Returns an object with two properties: `sql`, which is the transformed
     *   SQL query with `?` placeholders for `prepare()`; and `values`, which is
     *   an indexed array of ordered binding values for `bind_param()`.
     * @throws \InvalidArgumentException
     *   If a placeholder in the SQL string has no matching binding, or if a
     *   binding is provided that does not match any placeholder.
     */
    private static function transformQuery(Query $query): \stdClass
    {
        $bindings = $query->Bindings();
        $result = new \stdClass();
        $result->values = [];
        $result->sql = \preg_replace_callback(
            '/:(' . Query::IDENTIFIER_PATTERN . ')/',
            function($matches) use($result, $bindings) {
                $result->values[] = $bindings[$matches[1]];
                return '?';
            },
            $query->ToSql() // may throw
        );
        return $result;
    }

    #endregion private
}
