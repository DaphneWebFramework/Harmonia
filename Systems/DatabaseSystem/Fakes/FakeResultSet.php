<?php declare(strict_types=1);
/**
 * FakeResultSet.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\DatabaseSystem\Fakes;

use \Harmonia\Systems\DatabaseSystem\ResultSet;

/**
 * Simulates a database result set for testing purposes.
 *
 * This class is used by `FakeDatabase` to simulate the result of a database
 * query without requiring a real database connection. It provides an in-memory
 * result stream that mimics the behavior of `ResultSet`.
 */
class FakeResultSet extends ResultSet
{
    private array $rows;
    private int $cursor;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance with the given row data and initializes the
     * internal cursor.
     *
     * @param array<int, array<string, mixed>> $rows
     *   (Optional) The list of result rows. Defaults to an empty list.
     */
    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
        $this->cursor = 0;
    }

    /**
     * Retrieves the column names from the first row.
     *
     * @return array<string>
     *   An array of column names. Returns an empty array if no rows are defined.
     */
    public function Columns(): array
    {
        if (empty($this->rows)) {
            return [];
        }
        $firstRowIndex = \array_key_first($this->rows);
        $firstRow = $this->rows[$firstRowIndex];
        return \array_keys($firstRow);
    }

    /**
     * Retrieves the number of rows.
     *
     * @return int
     *   The number of rows. Returns `0` if no rows are defined.
     */
    public function RowCount(): int
    {
        return \count($this->rows);
    }

    /**
     * Retrieves the current row and advances the internal cursor.
     *
     * @param int $mode
     *   (Optional) Row mode. Use `ROW_MODE_ASSOCIATIVE` (default) to return
     *   rows as associative arrays, or `ROW_MODE_NUMERIC` for numerically
     *   indexed arrays.
     * @return array<string, mixed>|array<int, mixed>|null
     *   The current row, or `null` if there are no more rows.
     * @throws \InvalidArgumentException
     *   If an invalid row mode is provided.
     */
    public function Row(int $mode = self::ROW_MODE_ASSOCIATIVE): ?array
    {
        if (!\array_key_exists($this->cursor, $this->rows)) {
            return null;
        }
        $row = $this->rows[$this->cursor++];
        switch ($mode) {
        case self::ROW_MODE_ASSOCIATIVE:
            return $row;
        case self::ROW_MODE_NUMERIC:
            return \array_values($row);
        default:
            throw new \InvalidArgumentException("Invalid row mode: $mode");
        }
    }

    /**
     * Returns an iterator for traversing row by row.
     *
     * @return \Traversable
     *   An iterable object yielding rows as associative arrays.
     */
    public function getIterator(): \Traversable
    {
        while (($row = $this->Row()) !== null) {
            yield $row;
        }
    }

    #endregion public
}
