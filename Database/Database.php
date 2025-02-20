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
