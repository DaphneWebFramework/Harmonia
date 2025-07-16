<?php declare(strict_types=1);
/**
 * DataAccessor.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem;

use \Harmonia\Core\CArray;

/**
 * Provides access to structured data, supporting nested field resolution.
 */
class DataAccessor
{
    /**
     * The data source being accessed.
     *
     * @var array|object
     */
    private readonly array|object $data;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance with structured data.
     *
     * @param array|object $data
     *   The data to be accessed, either as an associative array or an object.
     *   If the data is an instance of `CArray`, it is converted to an array.
     */
    public function __construct(array|object $data)
    {
        if ($data instanceof CArray) {
            $data = $data->ToArray();
        }
        $this->data = $data;
    }

    /**
     * Returns the underlying data source.
     *
     * @return array|object
     *   The data source being accessed, which can be an array or an object.
     */
    public function Data(): array|object
    {
        return $this->data;
    }

    /**
     * Checks if the specified field exists in the data.
     *
     * Supports nested fields using dot notation (e.g., `'user.profile.name'`).
     *
     * @param string|int $field
     *   The field name or index to check.
     * @return bool
     *   Returns `true` if the field exists, `false` otherwise.
     */
    public function HasField(string|int $field): bool
    {
        if (self::isDottedField($field)) {
            $carry = $this->data;
            foreach (\explode('.', $field) as $subfield) {
                if (!self::hasSubfield($carry, $subfield)) {
                    return false;
                }
                $carry = self::getSubfield($carry, $subfield);
            }
            return true;
        } else {
            return self::hasSubfield($this->data, $field);
        }
    }

    /**
     * Retrieves the value of the specified field.
     *
     * Supports nested fields using dot notation (e.g., `'user.profile.name'`).
     *
     * @param string|int $field
     *   The field name or index to retrieve.
     * @return mixed
     *   The field value if it exists.
     * @throws \RuntimeException
     *   If the field does not exist.
     */
    public function GetField(string|int $field): mixed
    {
        if (!$this->HasField($field)) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'field_does_not_exist',
                $field
            ));
        }
        if (self::isDottedField($field)) {
            $carry = $this->data;
            foreach (\explode('.', $field) as $subfield) {
                $carry = self::getSubfield($carry, $subfield);
            }
            return $carry;
        } else {
            return self::getSubfield($this->data, $field);
        }
    }

    /**
     * Retrieves the value of the specified field or returns a default if not
     * found.
     *
     * Supports nested fields using dot notation (e.g., `'user.profile.name'`).
     *
     * @param string|int $field
     *   The field name or index to retrieve.
     * @param mixed $defaultValue
     *   The default value to return if the field does not exist.
     * @return mixed
     *   The field value if it exists, otherwise the default value.
     */
    public function GetFieldOrDefault(
        string|int $field,
        mixed $defaultValue = null
    ): mixed
    {
        if (!$this->HasField($field)) {
            return $defaultValue;
        }
        return $this->GetField($field);
    }

    #endregion public

    #region private ------------------------------------------------------------

    /**
     * Determines if the field name contains dot notation.
     *
     * @param string|int $field
     *   The field name to check.
     * @return bool
     *   Returns `true` if the field is in dot notation, `false` otherwise.
     */
    private static function isDottedField(string|int $field): bool
    {
        if (!\is_string($field)) {
            return false;
        }
        return \str_contains($field, '.');
    }

    /**
     * Checks if a subfield exists within an array or object.
     *
     * @param mixed $value
     *   The data structure (array or object) to check.
     * @param string|int $field
     *   The subfield key or property to check.
     * @return bool
     *   Returns `true` if the subfield exists, `false` otherwise.
     */
    private static function hasSubfield(mixed $value, string|int $field): bool
    {
        if (\is_array($value)) {
            return \array_key_exists($field, $value);
        }
        if (\is_object($value)) {
            return \property_exists($value, (string)$field);
        }
        return false;
    }

    /**
     * Retrieves a subfield from an array or object.
     *
     * @param array|object $value
     *   The data structure (array or object) from which to retrieve the subfield.
     * @param string|int $field
     *   The subfield key or property to retrieve.
     * @return mixed
     *   Returns the subfield value.
     */
    private static function getSubfield(array|object $value, string|int $field): mixed
    {
        if (\is_array($value)) {
            return $value[$field];
        }
        return $value->{$field};
    }

    #endregion private
}
