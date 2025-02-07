<?php declare(strict_types=1);
/**
 * IShutdownListener.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Shutdown;

/**
 * Interface for classes that listen to the shutdown process.
 */
interface IShutdownListener
{
    /**
     * The method to be called on shutdown.
     *
     * @param ?string $errorMessage
     *   An optional error message of the last error that occurred; otherwise,
     *   `null`. The message usually contains error details such as type,
     *   message, file, and line number.
     */
    public function OnShutdown(?string $errorMessage): void;
}
