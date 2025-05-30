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

class FakeResultSet extends ResultSet
{
    private array $rows;
    private int $cursor;

    #region public -------------------------------------------------------------

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
        $this->cursor = 0;
    }

    public function Columns(): array
    {
        if (empty($this->rows)) {
            return [];
        }
        return \array_keys($this->rows[\array_key_first($this->rows)]);
    }

    public function RowCount(): int
    {
        return \count($this->rows);
    }

    public function Row(int $mode = parent::ROW_MODE_ASSOCIATIVE): ?array
    {
        if (!\array_key_exists($this->cursor, $this->rows)) {
            return null;
        }
        $row = $this->rows[$this->cursor++];
        switch ($mode) {
        case parent::ROW_MODE_ASSOCIATIVE:
            return $row;
        case parent::ROW_MODE_NUMERIC:
            return \array_values($row);
        default:
            throw new \InvalidArgumentException("Invalid row mode: $mode");
        }
    }

    public function getIterator(): \Traversable
    {
        while (($row = $this->Row()) !== null) {
            yield $row;
        }
    }

    #endregion public
}
