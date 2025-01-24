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

    #endregion public
}
