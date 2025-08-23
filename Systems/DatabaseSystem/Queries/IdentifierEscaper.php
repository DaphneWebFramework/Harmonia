<?php declare(strict_types=1);
/**
 * IdentifierEscaper.php
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
 * Provides a utility method for escaping SQL identifiers.
 */
trait IdentifierEscaper
{
    /**
     * Escapes backtick characters in an identifier for use in an SQL statement.
     *
     * This method doubles any backticks found in the identifier. The returned
     * value is not enclosed in backticks automatically; it is the caller's
     * responsibility to wrap the result in backticks when embedding it directly
     * in SQL strings.
     *
     * @param string $identifier
     *   The identifier to escape, such as a table or column name.
     * @return string
     *   The escaped identifier, safe for use in SQL contexts when properly
     *   enclosed in backticks.
     */
    protected static function escapeIdentifier(string $identifier): string
    {
        return \str_replace('`', '``', $identifier);
    }
}
