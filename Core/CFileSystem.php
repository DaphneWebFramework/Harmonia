<?php declare(strict_types=1);
/**
 * CFileSystem.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Core;

use \Harmonia\Patterns\Singleton;

/**
 * Provides file system utility methods for directory and file management.
 */
class CFileSystem extends Singleton
{
    /**
     * Creates a directory.
     *
     * Returns successfully if the directory already exists.
     *
     * @param string|\Stringable $directoryPath
     *   The path of the directory to be created.
     * @param int $permissions
     *   (Optional) Permissions to set on the created directory. Defaults to
     *   `0755`, which means the owner has full permissions, and others have
     *   read and execute permissions.
     * @return bool
     *   Returns `true` if the directory is created successfully or it already
     *   exists. Otherwise, returns `false`.
     */
    public function CreateDirectory(
        string|\Stringable $directoryPath,
        int $permissions = 0755
    ): bool
    {
        $directoryPath = (string)$directoryPath;
        if (\is_dir($directoryPath)) {
            return true;
        }
        return mkdir($directoryPath, $permissions, true);
    }

    /**
     * Deletes a directory and all its contents.
     *
     * This method recursively deletes all files and subdirectories within the
     * specified directory before removing the directory itself.
     *
     * @param string|\Stringable $directoryPath
     *   The path of the directory to be deleted.
     * @return bool
     *   Returns `true` if the directory and its contents are deleted
     *   successfully. Otherwise, returns `false`.
     */
    public function DeleteDirectory(string|\Stringable $directoryPath): bool
    {
        $directoryPath = (string)$directoryPath;
        if (!\is_dir($directoryPath)) {
            return false;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directoryPath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            if ($path->isDir()) {
                \rmdir($path->getPathname());
            } else {
                \unlink($path->getPathname());
            }
        }
        return \rmdir($directoryPath);
    }

    /**
     * Deletes a file.
     *
     * @param string|\Stringable $filePath
     *   The path of the file to be deleted.
     * @return bool
     *   Returns `true` on success, or `false` on failure.
     */
    public static function DeleteFile(string|\Stringable $filePath): bool
    {
        $filePath = (string)$filePath;
        if (!\is_file($filePath)) {
            return false;
        }
        return \unlink($filePath);
    }
}
