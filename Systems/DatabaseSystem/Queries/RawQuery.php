<?php declare(strict_types=1);
/**
 * RawQuery.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\DatabaseSystem\Queries;

/**
 * Represents a predefined SQL query with optional bindings.
 *
 * #### Examples
 *
 * ```php
 * $query = (new RawQuery)
 *     ->Sql('DROP TABLE IF EXISTS `users_temp`');
 * ```
 *
 * ```php
 * $query = (new RawQuery)
 *     ->Sql('SHOW FULL TABLES FROM `mydb` LIKE :pattern')
 *     ->Bind(['pattern' => 'user_%']);
 * ```
 */
class RawQuery extends Query
{
    private ?string $sql = null;

    #region public -------------------------------------------------------------

    /**
     * Sets the SQL string.
     *
     * @param string $sql
     *   The SQL string. May include named placeholders (e.g., :name).
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the SQL string is empty or contains only whitespace.
     */
    public function Sql(string $sql): self
    {
        $this->sql = $this->checkString($sql);
        return $this;
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /**
     * Returns the SQL string.
     *
     * @return string
     *   The SQL string.
     * @throws \RuntimeException
     *   If the SQL string has not been set.
     */
    protected function buildSql(): string
    {
        if ($this->sql === null) {
            throw new \RuntimeException(
                'SQL string must be provided.');
        }
        return $this->sql;
    }

    #endregion protected
}
