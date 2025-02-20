<?php declare(strict_types=1);
/**
 * MySQLiStatement.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database\Proxies;

class MySQLiStatement
{
    private readonly \mysqli_stmt $object;

    public function __construct(\mysqli_stmt $object)
    {
        $this->object = $object;
    }

    public function get_result(): MySQLiResult|false
    {
        $result = $this->object->get_result();
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
