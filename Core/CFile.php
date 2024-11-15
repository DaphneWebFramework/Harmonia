<?php declare(strict_types=1);

/**
 * CFile.php
 *
 * (C) 2024 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Core;

/**
 * Provides an interface for file operations.
 */
class CFile
{
    /**
     * Opens a file for reading only. The file must exist.
     */
    public const MODE_READ = 'rb';

    /**
     * Creates an empty file for writing only. If the file exists, it is
     * truncated to zero (0) bytes.
     */
    public const MODE_WRITE = 'wb';

    /**
     * Opens a file with read/write permissions. Creates the file if it does
     * not exist.
     */
    public const MODE_READWRITE = 'c+b';

    /**
     * Opens a file for writing at the end. Creates the file if it does not exist.
     */
    public const MODE_APPEND = 'ab';

    /**
     * Sets the cursor relative to the beginning of the file.
     *
     * A positive offset moves the cursor forward from the start, while a
     * negative offset is not allowed and will result in an error.
     */
    public const ORIGIN_BEGIN = \SEEK_SET;

    /**
     * Sets the cursor relative to its current position.
     *
     * A positive offset moves the cursor forward, while a negative offset moves
     * it backward from the current position.
     */
    public const ORIGIN_CURRENT = \SEEK_CUR;

    /**
     * Sets the cursor relative to the end of the file.
     *
     * A negative offset moves the cursor backward from the file's end, while a
     * positive offset moves it forward, potentially beyond the end of the file.
     */
    public const ORIGIN_END = \SEEK_END;

    /**
     * The file handle resource.
     *
     * @var resource
     */
    private $handle;

    /**
     * Constructs a new instance with a file handle resource.
     *
     * @param resource $handle
     *   The file handle resource.
     */
    private function __construct($handle)
    {
        $this->handle = $handle;
    }

    /**
     * Destructor that closes the file handle if it hasn't been closed explicitly.
     */
    public function __destruct()
    {
        $this->Close();
    }

    /**
     * Opens a file and returns a new `CFile` instance.
     *
     * @param string $filename
     *   The name of the file to open.
     * @param string $mode (Optional)
     *   The mode for opening the file. Defaults to `CFile::MODE_READ`.
     * @return CFile|null
     *   A `CFile` instance if successful, or `null` on failure.
     */
    public static function Open(string $filename, string $mode = self::MODE_READ): ?CFile
    {
        $handle = self::_fopen($filename, $mode);
        if ($handle === false) {
            return null;
        }
        return new CFile($handle);
    }

    /**
     * Closes the file handle, making the instance unusable afterward.
     *
     * @return void
     */
    public function Close(): void
    {
        if ($this->handle !== null) {
            $this->_fclose();
            $this->handle = null;
        }
    }

    /**
     * Reads a specified number of bytes.
     *
     * This method allows multiple readers to access the file concurrently,
     * while preventing other processes from writing to the file until the
     * reading is complete. If another process is currently writing to the file,
     * this call will efficiently wait until the write operation is finished.
     *
     * @param ?int $length (Optional)
     *   The number of bytes to read. If omitted, reads until the end of the
     *   file.
     * @return ?string
     *   The read bytes as a string, or `null` on failure.
     */
    public function Read(?int $length = null): ?string
    {
        return $this->withLock(\LOCK_SH, function() use($length) {
            if ($length === null) {
                $cursor = $this->Cursor();
                if ($cursor === null) {
                    return null;
                }
                $length = $this->Size() - $cursor;
            }
            if ($length < 0) {
                return null;
            } elseif ($length === 0) {
                return '';
            }
            $bytes = $this->_fread($length);
            if ($bytes === false) {
                return null;
            }
            return $bytes;
        });
    }

    /**
     * Reads a line.
     *
     * This method allows multiple readers to access the file concurrently,
     * while preventing other processes from writing to the file until the
     * reading is complete. If another process is currently writing to the file,
     * this call will efficiently wait until the write operation is finished.
     *
     * @return ?string
     *   The line read without newline characters, or `null` on read failure.
     */
    public function ReadLine(): ?string
    {
        return $this->withLock(\LOCK_SH, function() {
            $line = $this->_fgets();
            if ($line === false) {
                return null;
            }
            return \rtrim($line, "\r\n");
        });
    }

    /**
     * Writes the specified bytes.
     *
     * This method prevents other processes from reading or writing to the file
     * while the write operation is in progress. If another process is currently
     * accessing the file, this call will efficiently wait until the file is
     * available for exclusive access.
     *
     * @param string $bytes
     *   The string containing the bytes to write.
     * @return bool
     *   Returns `true` if the write is successful, `false` otherwise.
     */
    public function Write(string $bytes): bool
    {
        return $this->withLock(\LOCK_EX, function() use($bytes) {
            if (false === $this->_fwrite($bytes)) {
                return false;
            }
            return true;
        }) ?? false;
    }

    /**
     * Writes a line.
     *
     * This method automatically writes a newline character (`\n`) after the
     * line is written.
     *
     * This method prevents other processes from reading or writing to the file
     * while the write operation is in progress. If another process is currently
     * accessing the file, this call will efficiently wait until the file is
     * available for exclusive access.
     *
     * @param string $line
     *   The line to write.
     * @return bool
     *   Returns `true` if the write is successful, `false` otherwise.
     */
    public function WriteLine(string $line): bool
    {
        return $this->Write($line . "\n");
    }

    /**
     * Returns the file size in bytes.
     *
     * @return int
     *   The file size in bytes, or `0` on failure.
     */
    public function Size(): int
    {
        $stat = $this->_fstat();
        if ($stat === false) {
            return 0;
        }
        return $stat['size'];
    }

    /**
     * Returns the current cursor position.
     *
     * @return int|null
     *   The current position of the file cursor, or `null` on failure.
     */
    public function Cursor(): ?int
    {
        $cursor = $this->_ftell();
        if ($cursor === false) {
            return null;
        }
        return $cursor;
    }

    /**
     * Sets the cursor to a specified position.
     *
     * @param int $offset
     *   The byte offset to move the cursor. This value is interpreted relative
     *   to the `$origin` parameter.
     * @param int $origin (Optional)
     *   The reference position for `$offset`. Must be one of `ORIGIN_BEGIN`,
     *   `ORIGIN_CURRENT`, or `ORIGIN_END`. Defaults to `ORIGIN_BEGIN`.
     * @return bool
     *   Returns `true` if the cursor was successfully moved, `false` otherwise.
     */
    public function SetCursor(int $offset, int $origin = self::ORIGIN_BEGIN): bool
    {
        return $this->_fseek($offset, $origin) === 0;
    }

    //-- protected -------------------------------------------------------------

    /**
     * Opens a file with the specified mode.
     *
     * @param string $filename
     *   The name of the file to open.
     * @param string $mode
     *   The mode for opening the file.
     * @return resource|false
     *   Returns a file handle on success, or `false` on failure.
     */
    protected static function _fopen(string $filename, string $mode)
    {
        return \fopen($filename, $mode);
    }

    /**
     * Closes the current file handle.
     *
     * @return bool
     *   Returns `true` on success or `false` on failure.
     */
    protected function _fclose(): bool
    {
        return \fclose($this->handle);
    }

    /**
     * Reads a specified number of bytes from the file.
     *
     * @param int $length
     *   The number of bytes to read.
     * @return string|false
     *   Returns the read bytes as a string on success, or `false` on failure.
     */
    protected function _fread(int $length)
    {
        return \fread($this->handle, $length);
    }

    /**
     * Reads a line from the file.
     *
     * @return string|false
     *   Returns the line read from the file on success, or `false` on failure.
     */
    protected function _fgets()
    {
        return \fgets($this->handle);
    }

    /**
     * Writes the specified bytes to the file.
     *
     * @param string $bytes
     *   The data to write.
     * @return int|false
     *   Returns the number of bytes written on success, or `false` on failure.
     */
    protected function _fwrite(string $bytes)
    {
        return \fwrite($this->handle, $bytes);
    }

    /**
     * Retrieves the file statistics.
     *
     * @return array|false
     *   Returns an array with file statistics on success, or `false` on failure.
     */
    protected function _fstat()
    {
        return \fstat($this->handle);
    }

    /**
     * Retrieves the current cursor position in the file.
     *
     * @return int|false
     *   Returns the current position of the file pointer on success, or `false`
     *   on failure.
     */
    protected function _ftell()
    {
        return \ftell($this->handle);
    }

    /**
     * Sets the cursor position within the file.
     *
     * @param int $offset
     *   The byte offset to move the cursor to.
     * @param int $origin
     *   The reference position for `$offset`. Use `ORIGIN_BEGIN`,
     *   `ORIGIN_CURRENT`, or `ORIGIN_END`.
     * @return int
     *   Returns `0` on success, or `-1` on failure.
     */
    protected function _fseek(int $offset, int $origin): int
    {
        return \fseek($this->handle, $offset, $origin);
    }

    /**
     * Acquires or releases a lock on the file.
     *
     * @param int $mode
     *   The locking mode, such as `LOCK_SH` for shared lock, `LOCK_EX` for
     *   exclusive lock, or `LOCK_UN` for unlocking.
     * @return bool
     *   Returns `true` on success or `false` on failure.
     */
    protected function _flock(int $mode): bool
    {
        return \flock($this->handle, $mode);
    }

    //-- private ---------------------------------------------------------------

    /**
     * Executes a callback with the specified lock mode.
     *
     * @param int $mode
     *   The locking mode, such as `LOCK_SH` for shared or `LOCK_EX` for
     *   exclusive.
     * @param callable $callback
     *   The callback to execute while holding the lock.
     * @return mixed
     *   The result of the callback, or `null` if locking fails.
     */
    protected function withLock(int $mode, callable $callback)
    {
        if (!$this->_flock($mode)) {
            return null;
        }
        try {
            return $callback();
        } finally {
            $this->_flock(\LOCK_UN);
        }
    }
}
