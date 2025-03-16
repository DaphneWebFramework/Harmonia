<?php declare(strict_types=1);
/**
 * Logger.php
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

use \Harmonia\Config;
use \Harmonia\Core\CFile;
use \Harmonia\Core\CFileSystem;
use \Harmonia\Core\CPath;
use \Harmonia\Resource;

/**
 * Logs messages to a file.
 *
 * Logging behavior is controlled via configuration:
 * - `LogFile`: Path to the log file. A relative path is prefixed with the
 *   application root directory, while an absolute path is used as is.
 * - `LogLevel`: Controls which messages are logged:
 *   - `0`: Logging is disabled.
 *   - `1`: Logs only error messages.
 *   - `2`: Logs warnings and errors.
 *   - `3`: Logs info, warnings, and errors.
 *
 * #### Examples
 *
 * ```php
 * Logger::Instance()->Info('Application started.');
 * ```
 *
 * Avoids unnecessary computation in case logging is disabled:
 * ```php
 * Logger::Instance()->Info(fn() => heavyComputation());
 * ```
 */
class Logger extends Singleton
{
    private const DEFAULT_FILENAME = 'default.log';

    private const LEVEL_NONE = 0;
    private const LEVEL_ERROR = 1;
    private const LEVEL_WARNING = 2;
    private const LEVEL_INFO = 3;

    private readonly CPath $filePath;
    private readonly int $level;

    /**
     * Constructs a new instance by initializing the log file path and log level.
     *
     * The log file path is retrieved from the configuration. If the path is
     * absolute, it is used directly. Otherwise, it is prefixed with the
     * application root directory.
     *
     * If the directory for the log file does not exist, it will be created
     * automatically.
     *
     * Configuration Options:
     * - `LogFile` (string): The path to the log file.
     * - `LogLevel` (int): Determines which messages will be logged. Possible
     *   values:
     *   - `0`: Logging is disabled.
     *   - `1`: Logs only error messages.
     *   - `2`: Logs warnings and errors.
     *   - `3`: Logs info, warnings, and errors.
     */
    protected function __construct()
    {
        $config = Config::Instance();
        $this->filePath = $this->buildFilePath(
            $config->OptionOrDefault('LogFile', self::DEFAULT_FILENAME));
        $this->ensureDirectoryExists();
        $this->level = $config->OptionOrDefault('LogLevel', self::LEVEL_INFO);
    }

    #region public -------------------------------------------------------------

    /**
     * Logs an informational message.
     *
     * @param string|callable $message
     *   The message to log, or a callable returning the message.
     */
    public function Info(string|callable $message): void
    {
        if ($this->level < self::LEVEL_INFO) {
            return;
        }
        $this->writeEntry($this->formatEntry(
            'INFO', $this->resolveMessage($message)));
    }

    /**
     * Logs a warning message.
     *
     * @param string|callable $message
     *   The message to log, or a callable returning the message.
     */
    public function Warning(string|callable $message): void
    {
        if ($this->level < self::LEVEL_WARNING) {
            return;
        }
        $this->writeEntry($this->formatEntry(
            'WARNING', $this->resolveMessage($message)));
    }

    /**
     * Logs an error message.
     *
     * @param string|callable $message
     *   The message to log, or a callable returning the message.
     */
    public function Error(string|callable $message): void
    {
        if ($this->level < self::LEVEL_ERROR) {
            return;
        }
        $this->writeEntry($this->formatEntry(
            'ERROR', $this->resolveMessage($message)));
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /**
     * Builds an absolute file path from a given filename.
     *
     * If the filename is already an absolute path, it is returned as is.
     * Otherwise, it is prefixed with the application root directory.
     *
     * This method does not verify whether the file or its parent directories
     * exist, as the file may not have been created yet.
     *
     * @param string $filename
     *   The filename to build the path for.
     * @return CPath
     *   The absolute file path.
     */
    protected function buildFilePath(string $filename): CPath
    {
        if ($this->isAbsolutePath($filename)) {
            return new CPath($filename);
        }
        return CPath::Join(Resource::Instance()->AppPath(), $filename);
    }

    /**
     * Determines whether the given path is absolute.
     *
     * On Linux/macOS, a path is absolute if it starts with "/".
     *
     * On Windows, a path is absolute if it:
     * - Begins with a drive letter followed by ":\" (e.g., "C:\path")
     * - Starts with "\\" (UNC paths like "\\server\share")
     * - Starts with "\\?\" (Long Path syntax like "\\?\C:\...")
     *
     * On Windows, any "/" characters are converted to "\" before checking.
     *
     * @param string $path
     *   The path to check.
     * @return bool
     *   Returns `true` if the path is absolute, `false` otherwise.
     */
    protected function isAbsolutePath(string $path): bool
    {
        if (\DIRECTORY_SEPARATOR === '/') {
            // Linux/macOS
            return \str_starts_with($path, '/');
        } else {
            // Windows
            $path = \str_replace('/', '\\', $path);
            return \preg_match('/^[a-zA-Z]:\\\\/', $path) === 1
                || \str_starts_with($path, '\\\\');
        }
    }

    /**
     * Ensures the directory for the log file exists.
     *
     * If the directory does not exist, an attempt is made to create it.
     * If creation fails, no error is raised.
     */
    protected function ensureDirectoryExists(): void
    {
        $dirPath = $this->filePath->Apply('dirname');
        if (!$dirPath->IsDirectory()) {
            CFileSystem::Instance()->CreateDirectory($dirPath);
        }
    }

    /**
     * Resolves the log message, evaluating it if it's a callable.
     *
     * @param string|callable $message
     *   The log message or a callable returning a string.
     * @return string
     *   The evaluated log message.
     */
    protected function resolveMessage(string|callable $message): string
    {
        return \is_callable($message) ? $message() : $message;
    }

    /**
     * Formats the log message with timestamp, client's IP address, and context.
     *
     * @param string $context
     *   The log level context ("INFO", "WARNING", "ERROR").
     * @param string $message
     *   The log message.
     * @return string
     *   The formatted log entry.
     */
    protected function formatEntry(string $context, string $message): string
    {
        $timestamp = (new \DateTime)->format('Y-m-d H:i:s');
        $clientAddress = Server::Instance()->ClientAddress();
        return "[$timestamp | $clientAddress] $context: $message";
    }

    /**
     * Writes the log entry to the file.
     *
     * @param string $entry
     *   The log entry to write.
     */
    protected function writeEntry(string $entry): void
    {
        $file = $this->openFile($this->filePath, CFile::MODE_APPEND);
        if ($file !== null) {
            $file->WriteLine($entry);
            $file->Close();
        }
    }

    /** @codeCoverageIgnore */
    protected function openFile(CPath $filePath, string $mode): ?CFile
    {
        return CFile::Open($filePath, $mode);
    }

    #endregion protected
}
