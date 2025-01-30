<?php declare(strict_types=1);
/**
 * Config.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia;

use \Harmonia\Patterns\Singleton;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CPath;

/**
 * Provides structured access to configuration options.
 */
class Config extends Singleton
{
    /**
     * Stores configuration options.
     *
     * @var CArray
     */
    private CArray $options;

    /**
     * Stores the path to the configuration options file.
     *
     * @var ?CPath
     */
    private ?CPath $optionsFilePath;

    /**
     * Constructs a new instance with empty configuration options.
     */
    protected function __construct()
    {
        $this->options = new CArray();
        $this->optionsFilePath = null;
    }

    #region public -------------------------------------------------------------

    /**
     * Retrieves the configuration options.
     *
     * @return CArray
     *   The configuration options.
     */
    public function Options(): CArray
    {
        return $this->options;
    }

    /**
     * Retrieves the path to the configuration options file.
     *
     * @return ?CPath
     *   The path to the configuration options file, or `null` if no file is
     *   loaded.
     */
    public function OptionsFilePath(): ?CPath
    {
        return $this->optionsFilePath;
    }

    /**
     * Loads configuration options from the specified file.
     *
     * @param CPath $optionsFilePath
     *   The path to the configuration options file.
     * @throws \InvalidArgumentException
     *   If the specified file does not exist.
     */
    public function Load(CPath $optionsFilePath): void
    {
        if (!$optionsFilePath->IsFile()) {
            throw new \InvalidArgumentException(
                "Configuration options file not found: $optionsFilePath");
        }
        $this->optionsFilePath = $optionsFilePath;
        $this->options = new CArray(include $optionsFilePath);
    }

    /**
     * Reloads configuration options from the file.
     *
     * @throws \RuntimeException
     *   If no configuration options file is loaded.
     */
    public function Reload(): void
    {
        if ($this->optionsFilePath === null) {
            throw new \RuntimeException('No configuration options file is loaded.');
        }
        if (\function_exists('opcache_invalidate')) {
            \opcache_invalidate((string)$this->optionsFilePath, true);
        }
        $this->options = new CArray(include $this->optionsFilePath);
    }

    /**
     * Retrieves the value of a configuration option.
     *
     * @param string $key
     *   The key of the configuration option.
     * @return mixed
     *   The value of the configuration option, or `null` if the key is not found.
     */
    public function Option(string $key): mixed
    {
        return $this->options->Get($key);
    }

    /**
     * Sets the value of a configuration option.
     *
     * @param string $key
     *   The key of the configuration option.
     * @param mixed $value
     *   The value of the configuration option.
     * @throws \RuntimeException
     *   If the specified key is not found or if the value type does not match
     *   the existing value type.
     */
    public function SetOption(string $key, mixed $value): void
    {
        $currentValue = $this->options->Get($key);
        if ($currentValue === null) {
            throw new \RuntimeException("Configuration option not found: $key");
        }
        if (\gettype($value) !== \gettype($currentValue)) {
            throw new \RuntimeException("Configuration option type mismatch: $key");
        }
        $this->options->Set($key, $value);
    }

    #endregion public
}
