<?php declare(strict_types=1);
/**
 * MySQLiResult.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database;

class MySQLiResult
{
    private readonly \mysqli_result $object;

    public function __construct(\mysqli_result $object)
    {
        $this->object = $object;
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
