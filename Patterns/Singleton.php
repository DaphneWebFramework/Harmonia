<?php
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
 * Base class for implementing Singleton design pattern.
 *
 * This abstract class provides the basic mechanism to ensure that only one
 * instance of a subclass exists throughout the application. It uses a static
 * array to store instances of subclasses.
 */
abstract class Singleton
{
	/**
	 * Returns the singleton instance of the subclass.
	 *
	 * This static method manages the instantiation of `Singleton` subclasses.
	 * It creates a new instance if one doesn't already exist and returns the
	 * existing instance if it does.
	 *
	 * @return static
	 *   The singleton instance of the subclass.
	 */
	public static function GetInstance()
	{
		$key = static::class;
		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = new static();
		}
		return self::$instances[$key];
	}

	/**
	 * Protected constructor to prevent direct creation of object.
	 *
	 * The constructor is marked as protected to prevent creating new instances
	 * outside of the class, ensuring the singleton nature of the subclass.
	 */
	protected function __construct() {}

	/**
	 * Prevents cloning of the instance.
	 *
	 * Marking the `__clone` method as protected prevents cloning of the
	 * instance of the `Singleton` subclass, which helps in maintaining a single
	 * instance in the application.
	 */
	protected function __clone() {}

	/**
	 * Prevents unserialization of the instance.
	 *
	 * @throws \RuntimeException
	 *   If a `Singleton` instance is attempted to be unserialized. This
	 *   prevents the creation of multiple instances via unserialization.
	 * @internal
	 */
	public function __wakeup()
	{
		throw new \RuntimeException('Cannot unserialize a singleton.');
	}

	/**
	 * Stores the instance(s) of `Singleton` subclass(es).
	 *
	 * @var array<string, Singleton>
	 *   An associative array where the key is the subclass name and the value
	 *   is the instance.
	 */
	private static $instances = [];
}
