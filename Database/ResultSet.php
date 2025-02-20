<?php declare(strict_types=1);
/**
 * ResultSet.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database;

use \Harmonia\Database\Proxies\MySQLiResult;

/**
 * Represents a result set from a database query.
 */
class ResultSet implements \IteratorAggregate
{
    /**
     * Constant for the `Row` method to use column names as keys.
     *
     * @var int
     */
    public const ROW_MODE_ASSOCIATIVE = 1;

    /**
     * Constant for the `Row` method to use zero-based column indices as keys.
     *
     * @var int
     */
    public const ROW_MODE_NUMERIC = 2;

    /**
     * The underlying result object, or `null` if the result set is empty.
     *
     * @var ?MySQLiResult
     */
    private ?MySQLiResult $result = null;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * @param ?MySQLiResult $result
     *   (Optional) A result object, or `null` to represent an empty result set.
     *   Defaults to `null`.
     */
    public function __construct(?MySQLiResult $result = null)
    {
        $this->result = $result;
    }

    /**
     * Releases the memory associated with the result set.
     */
    public function __destruct()
    {
        if ($this->result !== null) {
            $this->result->free();
            $this->result = null;
        }
    }

    /**
     * Retrieves a single row from the result set.
     *
     * @param int $mode
     *   (Optional) Determines how keys in the returned array are indexed.
     *   Possible values are `ROW_MODE_ASSOCIATIVE` (default) for associative
     *   arrays and `ROW_MODE_NUMERIC` for numerically indexed arrays.
     * @return array<string, mixed>|array<int, mixed>|null
     *   An associative or indexed array representing the row, or `null` if the
     *   result set is empty or if the end of the result set has been reached.
     * @throws \InvalidArgumentException
     *   If an invalid key mode is provided.
     */
    public function Row(int $mode = self::ROW_MODE_ASSOCIATIVE): ?array
    {
        if ($this->result === null) {
            return null;
        }
        switch ($mode) {
        case self::ROW_MODE_ASSOCIATIVE:
            return $this->result->fetch_assoc() ?: null;
        case self::ROW_MODE_NUMERIC:
            return $this->result->fetch_row() ?: null;
        default:
            throw new \InvalidArgumentException("Invalid row mode: $mode");
        }
    }

    #region Interface: IteratorAggregate

    /**
     * Returns an iterator for traversing the result set row by row.
     *
     * @return \Traversable
     *   An iterable object yielding rows as associative arrays.
     */
    public function getIterator(): \Traversable
    {
        while ($row = $this->Row()) {
            yield $row;
        }
    }

    #endregion Interface: IteratorAggregate

    #endregion public
}
