<?php declare(strict_types=1);
/**
 * Database.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database;

use \Harmonia\Patterns\Singleton;

use \Harmonia\Config;
use \Harmonia\Database\Proxies\MySQLiResult;
use \Harmonia\Database\Queries\Query;

/**
 * Provides a central interface for working with databases.
 *
 * This class manages connections and executes queries on the application
 * database. Connection details are retrieved from the configuration.
 */
class Database extends Singleton
{
    /**
     * Represents the connection to the database server.
     *
     * This property is lazy-initialized. Therefore, never use this property
     * directly, use the `connection` method instead.
     *
     * @var ?Connection
     */
    private ?Connection $connection = null;

    #region public -------------------------------------------------------------

    /**
     * Executes a query on the database.
     *
     * @param Query $query
     *   The query object containing SQL and optionally its bindings.
     * @return ?MySQLiResult
     *   A `ResultSet` object, or `null` if a connection to the database server
     *   cannot be established or execution fails.
     */
    public function Execute(Query $query): ?ResultSet
    {
        $connection = $this->connection();
        if ($connection === null) {
            return null;
        }
        try {
            $result = $connection->Execute($query);
            return new ResultSet($result);
        } catch (\RuntimeException $e) {
            // todo: log the error
            return null;
        }
    }

    /**
     * Retrieves the last inserted row's ID.
     *
     * @return int
     *   The last inserted row's ID. The method returns `0` if the connection to
     *   the database server cannot be established or if the last query was not
     *   an `INSERT` or no `AUTO_INCREMENT` value was generated.
     */
    public function LastInsertId(): int
    {
        $connection = $this->connection();
        if ($connection === null) {
            return 0;
        }
        return $connection->LastInsertId();
    }

    /**
     * Retrieves the number of rows affected by the last query.
     *
     * @return int
     *   The number of rows affected by the last modifying query. Returns `0` if
     *   no rows were affected. Returns `-1` if the connection to the database
     *   server cannot be established or the last query has failed.
     */
    public function LastAffectedRowCount(): int
    {
        $connection = $this->connection();
        if ($connection === null) {
            return -1;
        }
        return $connection->LastAffectedRowCount();
    }

    #endregion public

    #region private ------------------------------------------------------------

    /**
     * Lazily initializes and retrieves the database connection.
     *
     * This method connects to the database management system (DBMS) and selects
     * the default database. All required connection details are obtained from
     * the application configuration.
     *
     * @return ?Connection
     *   The established database connection, or `null` if the connection fails.
     */
    private function connection(): ?Connection
    {
        if ($this->connection === null) {
            $config = Config::Instance();
            try {
                $connection = $this->_new_Connection(
                    $config->OptionOrDefault('DatabaseHostname', ''),
                    $config->OptionOrDefault('DatabaseUsername', ''),
                    $config->OptionOrDefault('DatabasePassword', ''),
                    $config->Option('DatabaseCharset')
                );
            } catch (\RuntimeException $e) {
                // todo: log the error
                return null;
            }
            try {
                $connection->SelectDatabase(
                    $config->OptionOrDefault('DatabaseName', '')
                );
            } catch (\RuntimeException $e) {
                // todo: log the error
                return null;
            }
            $this->connection = $connection;
        }
        return $this->connection;
    }

    #endregion private

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _new_Connection(string $hostname, string $username,
        string $password, ?string $charset): Connection
    {
        return new Connection($hostname, $username, $password, $charset); // may throw
    }

    #endregion protected
}
