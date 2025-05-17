<?php declare(strict_types=1);
/**
 * ShutdownHandler.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ShutdownSystem;

use \Harmonia\Patterns\Singleton;

use \Harmonia\Core\CSequentialArray;

class ShutdownHandler extends Singleton
{
    private readonly CSequentialArray $listeners;

    /**
     * Constructs a new instance by disabling PHP error display and registering
     * the shutdown function.
     */
    protected function __construct()
    {
        $this->listeners = new CSequentialArray();
        $this->_ini_set('display_errors', 0);
        $this->_register_shutdown_function([$this, 'OnShutdown']);
    }

    #region public -------------------------------------------------------------

    /**
     * Adds a listener to the shutdown process.
     *
     * @param IShutdownListener $listener
     *   The listener to add.
     */
    public function AddListener(IShutdownListener $listener): void
    {
        $this->listeners->PushBack($listener);
    }

    /**
     * The shutdown function that is called when the script ends.
     *
     * This function calls the `OnShutdown` method of each listener and passes
     * an error message if an error occurred during script execution.
     */
    public function OnShutdown(): void
    {
        $errorMessage = null;
        $error = $this->_error_get_last();
        if ($error !== null) {
            $errorMessage = self::formatErrorMessage($error);
        }
        foreach ($this->listeners as $listener) {
            $listener->OnShutdown($errorMessage);
        }
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _ini_set(string $option, mixed $value): void
    {
        if (\ini_set($option, $value) === false) {
            throw new \RuntimeException('Failed to set initialization option.');
        }
    }

    /** @codeCoverageIgnore */
    protected function _register_shutdown_function(callable $callback): void
    {
        \register_shutdown_function($callback);
    }

    /** @codeCoverageIgnore */
    protected function _error_get_last(): ?array
    {
        return \error_get_last();
    }

    #endregion protected

    #region private ------------------------------------------------------------

    /**
     * Returns an error message formatted from the given error details.
     *
     * @param array $error
     *   An associative array containing error details, typically from
     *   `error_get_last()`. The array should contain the following keys:
     *   `type`, `message`, `file`, and `line`.
     * @return string
     *   A formatted error message, e.g., "E_WARNING: Division by zero in
     *   'file.php' on line 123.".
     */
    private static function formatErrorMessage(array $error): string
    {
        $message = '';
        $errorTypeName = self::findErrorTypeName($error['type']);
        if ($errorTypeName !== '') {
            $message = "{$errorTypeName}: ";
        }
        $message .= $error['message'];
        if ($error['file'] !== 'Unknown') {
            $message .= " in '{$error['file']}'";
        }
        if ($error['line'] !== 0) {
            $message .= " on line {$error['line']}";
        }
        $message .= '.';
        return $message;
    }

    /**
     * Finds the error type name associated with a given error level.
     *
     * @param int $errorType
     *   The error type constant (e.g., `E_WARNING`, `E_NOTICE`).
     * @return string
     *   The name of the error type, or an empty string if not found.
     */
    private static function findErrorTypeName(int $errorType): string
    {
        foreach (\get_defined_constants(true)['Core'] as $name => $value) {
            if (\str_starts_with($name, 'E_') && $value === $errorType) {
                return $name;
            }
        }
        return '';
    }

    #endregion private
}
