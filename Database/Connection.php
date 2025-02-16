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

namespace Harmonia\Database;

use \Harmonia\Database\Queries\Query;

/**
 * Represents a connection to a MySQL server.
 */
class Connection
{
    /**
     * Handle to the MySQL connection.
     *
     * @var MySQLiHandle
     */
    private readonly MySQLiHandle $handle;

    #region public -------------------------------------------------------------

    /**
     * Opens a connection to a MySQL server.
     *
     * @param string $hostname
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
    public function __construct(
        string $hostname,
        string $username,
        string $password,
        ?string $charset = null
    ) {
        $this->handle = $this->connect($hostname, $username, $password);
        if ($this->handle->connect_errno !== 0) {
            throw new \RuntimeException($this->handle->connect_error,
                                        $this->handle->connect_errno);
        }
        if ($charset !== null && !$this->handle->set_charset($charset)) {
            $message = $this->handle->error;
            $code = $this->handle->errno;
            $this->handle->close();
            throw new \RuntimeException($message, $code);
        }
    }

    /**
     * Closes the connection to the MySQL server.
     */
    public function __destruct()
    {
        $this->handle->close();
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
        if (!$this->handle->select_db($databaseName)) {
            throw new \RuntimeException($this->handle->error, $this->handle->errno);
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
     *   A `MySQLiResult` if the query produces a result set, `null` otherwise.
     * @throws \RuntimeException
     *   If the query preparation or execution fails.
     */
    public function Execute(Query $query): ?MySQLiResult
    {
        $result = null;
        $query = $this->transformQuery($query);
        if (PHP_VERSION_ID < 80200)
        {
            $stmt = $this->prepareStatement($query->sql);
            if ($stmt === null) {
                throw new \RuntimeException($this->handle->error,
                                            $this->handle->errno);
            }
            if (!$stmt->bind_param($query->types, ...$query->values)) {
                $stmt->close();
                throw new \RuntimeException($stmt->error, $stmt->errno);
            }
            if ($stmt->execute() === false) {
                $stmt->close();
                throw new \RuntimeException($stmt->error, $stmt->errno);
            }
            $result = $stmt->get_result();
            if ($result === null && $stmt->errno !== 0) {
                $stmt->close();
                throw new \RuntimeException($stmt->error, $stmt->errno);
            }
            $stmt->close();
        }
        else // PHP >= 8.2.0
        {
            $result = $this->executeQuery($query->sql, $query->values);
            if ($result === false) {
                throw new \RuntimeException($this->handle->error, $this->handle->errno);
            } else if ($result === true) {
                $result = null; // For empty result sets.
            }
        }
        return $result;
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function connect(string $hostname, string $username,
        string $password): MySQLiHandle
    {
        $handle = new \mysqli($hostname, $username, $password);
        return new MySQLiHandle($handle);
    }

    /** @codeCoverageIgnore */
    protected function prepareStatement(string $sql): ?MySQLiStatement
    {
        $stmt = $this->handle->prepare($sql);
        if ($stmt === false) {
            return null;
        }
        return new MySQLiStatement($stmt);
    }

    /** @codeCoverageIgnore */
    protected function executeQuery(string $sql, array $values): MySQLiResult|bool
    {
        $result = $this->handle->execute_query($sql, $values);
        if ($result instanceof \mysqli_result) {
            return new MySQLiResult($result);
        }
        return $result;
    }

    #endregion protected

    #region private ------------------------------------------------------------

    /**
     * Converts a query with named placeholders into MySQLi-compatible format.
     *
     * This method replaces named placeholders (e.g., `:id`, `:name`) with `?`
     * for MySQLi `prepare()`, reorders binding values to match their appearance
     * in the query, and generates a type string for `bind_param()`.
     *
     * @param Query $query
     *   The query object containing SQL and its bindings.
     * @return \stdClass
     *   Returns an object with three properties: `sql`, which is the transformed
     *   SQL query with `?` placeholders for `prepare()`; `values`, which is an
     *   indexed array of ordered binding values for `bind_param()`; and `types`,
     *   which is a string representing the parameter types for `bind_param()`
     *   (e.g., `"isd"`).
     * @throws \InvalidArgumentException
     *   If a placeholder in the SQL string has no matching binding, or if a
     *   binding is provided that does not match any placeholder.
     */
    private function transformQuery(Query $query): \stdClass
    {
        $bindings = $query->Bindings();
        $result = new \stdClass();
        $result->types = '';
        $result->values = [];
        $result->sql = \preg_replace_callback(
            '/:(' . Query::IDENTIFIER_PATTERN . ')/',
            function($matches) use($result, $bindings) {
                $value = $bindings[$matches[1]];
                if (\is_int($value)) {
                    $result->types .= 'i';
                } elseif (\is_float($value)) {
                    $result->types .= 'd';
                } else {
                    $result->types .= 's';
                }
                $result->values[] = $value;
                return '?';
            },
            $query->ToSql() // may throw
        );
        return $result;
    }

    #endregion private
}
