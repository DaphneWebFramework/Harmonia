<?php declare(strict_types=1);
/**
 * MySQLiHandle.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database\Proxies;

class MySQLiHandle
{
    private readonly \mysqli $object;

    public function __construct(\mysqli $object)
    {
        $this->object = $object;
    }

    public function prepare(string $query): MySQLiStatement|false
    {
        $stmt = $this->object->prepare($query);
        if ($stmt instanceof \mysqli_stmt) {
            return new MySQLiStatement($stmt);
        }
        return $stmt;
    }

    public function execute_query(string $query, ?array $params): MySQLiResult|bool
    {
        $result = $this->object->execute_query($query, $params);
        if ($result instanceof \mysqli_result) {
            return new MySQLiResult($result);
        }
        return $result;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->object->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->object->$name;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->object->$name = $value;
    }
}
