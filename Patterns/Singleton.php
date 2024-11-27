<?php declare(strict_types=1);
/**
 * Singleton.php
 *
 * (C) 2024 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Patterns;

/**
 * Base class for implementing the Singleton design pattern.
 *
 * This abstract class ensures that only one instance of any subclass exists
 * throughout the application. It uses a static array to store instances of
 * subclasses.
 */
abstract class Singleton
{
    /**
     * Stores instances of the `Singleton` subclasses.
     *
     * @var array<string, static>
     *   An associative array where the key is the subclass name and the value
     *   is the instance of that subclass.
     */
    private static array $instances = [];

    /**
     * Returns the singleton instance of the subclass.
     *
     * This static method ensures that a single instance of a `Singleton`
     * subclass exists. It creates a new instance if one doesn't exist, or
     * returns the existing instance otherwise.
     *
     * @return static
     *   The singleton instance of the subclass.
     */
    public static function Instance(): static
    {
        $key = static::class;
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new static();
        }
        return self::$instances[$key];
    }

    /**
     * Replaces the singleton instance for the subclass.
     *
     * This method replaces the current singleton instance with a new one or
     * resets it if `null` is provided. It is primarily intended for scenarios
     * like testing, dynamic configuration updates, or context-specific
     * overrides.
     *
     * @param ?self $newInstance
     *   The new singleton instance to set, or `null` to reset the instance.
     * @return ?static
     *   The previous instance before replacement, or `null` if no instance
     *   existed.
     */
    public static function ReplaceInstance(?self $newInstance): ?static
    {
        $key = static::class;
        $previousInstance = self::$instances[$key] ?? null;
        if ($newInstance !== null) {
            self::$instances[$key] = $newInstance;
        } else {
            unset(self::$instances[$key]);
        }
        return $previousInstance;
    }

    /**
     * Prevents direct instantiation of the object.
     *
     * This constructor is not publicly accessible to prevent creating instances
     * outside of the class, ensuring the singleton nature. It is protected to
     * allow flexibility for subclass extensions.
     */
    protected function __construct() {}

    /**
     * Prevents cloning of the instance.
     *
     * This magic method is not publicly accessible to prevent cloning of the
     * singleton instance. It is protected to allow flexibility for subclass
     * extensions.
     */
    protected function __clone(): void {}

    /**
     * Prevents unserialization of the instance.
     *
     * While `__construct` and `__clone` are not publicly accessible, `__wakeup`
     * must remain public due to a requirement of the PHP engine, otherwise a
     * PHP warning would be triggered.
     *
     * @throws \RuntimeException
     *   If a `Singleton` instance is attempted to be unserialized.
     * @internal
     */
    public function __wakeup(): void
    {
        throw new \RuntimeException('Cannot unserialize a singleton.');
    }
}
