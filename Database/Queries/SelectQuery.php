<?php declare(strict_types=1);
/**
 * SelectQuery.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database\Queries;

/**
 * Class for building SQL SELECT queries.
 */
class SelectQuery extends Query
{
    /**
     * The columns to be selected. Default is '*'.
     *
     * @var string
     */
    private string $columns = '*';

    /**
     * The condition for the WHERE clause.
     *
     * @var ?string
     */
    private ?string $condition = null;

    /**
     * The ORDER BY clause.
     *
     * @var ?string
     */
    private ?string $orderBy = null;

    /**
     * The LIMIT clause.
     *
     * @var ?string
     */
    private ?string $limit = null;

    #region public -------------------------------------------------------------

    /**
     * Specifies the columns to retrieve in the query.
     *
     * @param array<int, string> $columns
     *   An array of column names or expressions. For example, `['column1',
     *   'COUNT(*) AS count']`.
     * @return self
     *   The current instance.
     */
    public function Select(array $columns): self
    {
        $this->columns = \implode(
            ', ',
            \array_map(function($column) {
                if ($this->isIdentifier($column)) {
                    return "`{$column}`";
                } else {
                    return $column;
                }
            }, $columns)
        );
        return $this;
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * @param string $condition
     *   The WHERE condition. For example, `"id = :id AND name = :name"`.
     * @param array<string, mixed> $substitutions
     *   (Optional) Key-value pairs for placeholders in the condition. For
     *   example, `['id' => 42, 'name' => 'John']`.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If placeholders in the condition do not match substitutions.
     */
    public function Where(string $condition, array $substitutions = []): self
    {
        \preg_match_all('/:\w+/', $condition, $matches);
        $placeholders = isset($matches[0]) ? $matches[0] : [];
        foreach ($placeholders as $index => $placeholder) {
            $placeholders[$index] = \substr($placeholder, 1);
        }
        if (!empty($placeholders) || !empty($substitutions)) {
            $substitutionKeys = \array_keys($substitutions);
            if ($diff = \array_diff($placeholders, $substitutionKeys)) {
                throw new \InvalidArgumentException(
                    'Missing substitutions: ' . \implode(', ', $diff));
            }
            if ($diff = \array_diff($substitutionKeys, $placeholders)) {
                throw new \InvalidArgumentException(
                    'Missing placeholders: ' . \implode(', ', $diff));
            }
        }
        $this->condition = $condition;
        $this->substitutions = $substitutions;
        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param array<int|string, string> $columns
     *   Associative or indexed array of column names and their sorting direction.
     *   For example, `['column1' => 'ASC', 'column2' => 'DESC', 'column3']`.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If an invalid sorting direction is provided. Valid directions are 'ASC'
     *   and 'DESC'.
     */
    public function OrderBy(array $columns): self
    {
        $parts = [];
        foreach ($columns as $key => $value) {
            if (\is_int($key)) {
                $column = $value;
                $direction = null;
            } else {
                $column = $key;
                $direction = \strtoupper($value);
            }
            if ($this->isIdentifier($column)) {
                $column = "`{$column}`";
            }
            if ($direction !== null) {
                if (!\in_array($direction, ['ASC', 'DESC'], true)) {
                    throw new \InvalidArgumentException(
                        "Invalid sorting direction: {$direction}");
                }
                $parts[] = "{$column} {$direction}";
            } else {
                $parts[] = $column;
            }
        }
        $this->orderBy = empty($parts) ? null : \implode(', ', $parts);
        return $this;
    }

    /**
     * Adds a LIMIT clause to the query.
     *
     * @param int $limit
     *   The maximum number of rows to return.
     * @param ?int $offset
     *   (Optional) The number of rows to skip before starting to return rows.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the limit or offset is negative.
     */
    public function Limit(int $limit, ?int $offset = null): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException(
                'Limit must be a non-negative integer.');
        }
        if ($offset !== null && $offset < 0) {
            throw new \InvalidArgumentException(
                'Offset must be a non-negative integer.');
        }
        if ($offset !== null) {
            $this->limit = "{$limit} OFFSET {$offset}";
        } else {
            $this->limit = (string)$limit;
        }
        return $this;
    }

    /**
     * Generates the SQL string representation of the query.
     *
     * @return string
     *   The SQL query string.
     * @override
     */
    public function ToSql(): string
    {
        $sql = "SELECT {$this->columns} FROM `{$this->tableName}`";
        if ($this->condition !== null) {
            $sql .= " WHERE {$this->condition}";
        }
        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        return $sql;
    }

    #endregion public
}
