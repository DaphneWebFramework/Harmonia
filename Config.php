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

use \Harmonia\Core\CPath;
use \Harmonia\Core\CArray;

/**
 * Provides structured access to configuration options.
 */
class Config extends Singleton
{
    /**
     * Stores the path to the configuration options file.
     *
     * @var ?CPath
     */
    private ?CPath $optionsFilePath;

    /**
     * Stores configuration options.
     *
     * @var ?CArray
     */
    private ?CArray $options;

    /**
     * Constructs a new instance.
     */
    protected function __construct()
    {
        $this->optionsFilePath = null;
        $this->options = null;
    }

    #region public -------------------------------------------------------------

    /**
     * Retrieves the path to the configuration options file.
     *
     * @return ?CPath
     *   The path to the configuration options file, or `null` if no file is
     *   loaded.
     */
    public function GetOptionsFilePath(): ?CPath
    {
        return $this->optionsFilePath;
    }

    /**
     * Retrieves configuration options.
     *
     * @return ?CArray
     *   The configuration options, or `null` if no options are loaded.
     */
    public function GetOptions(): ?CArray
    {
        return $this->options;
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

    #endregion public
}
