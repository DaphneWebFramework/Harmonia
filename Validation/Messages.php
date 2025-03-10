<?php declare(strict_types=1);
/**
 * Messages.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Validation;

use \Harmonia\Translation;

use \Harmonia\Core\CPath;

/**
 * Manages the messages used for validation, supporting multiple languages.
 */
class Messages extends Translation
{
    /**
     * Specifies the JSON file containing validation messages.
     *
     * @return array<CPath>
     *   A single-element array with the path to the JSON file containing
     *   validation messages.
     */
    protected function filePaths(): array
    {
        return [CPath::Join(__DIR__, 'messages.json')];
    }
}
