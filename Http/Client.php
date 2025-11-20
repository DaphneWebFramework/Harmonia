<?php declare(strict_types=1);
/**
 * Client.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Http;

/**
 * A wrapper for PHP's cURL extension for making HTTP requests.
 */
class Client
{
    private ?\CurlHandle $curl;
    private readonly \stdClass $request;
    private readonly \stdClass $response;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * @throws \RuntimeException
     *   If cURL fails to initialize.
     */
    public function __construct()
    {
        $curl = $this->_curl_init();
        if ($curl === false) {
            $this->curl = null;
            throw new \RuntimeException("Failed to initialize cURL.");
        }
        $this->curl = $curl;
    }

    /**
     * Closes the cURL handle.
     */
    public function __destruct()
    {
        if ($this->curl instanceof \CurlHandle) {
            if (\PHP_VERSION_ID < 80500) {
                $this->_curl_close();
            }
            $this->curl = null;
        }
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _curl_init(): \CurlHandle|false
    {
        return \curl_init();
    }

    /** @codeCoverageIgnore */
    protected function _curl_close(): void
    {
        \curl_close($this->curl);
    }

    /** @codeCoverageIgnore */
    protected function _curl_setopt(int $option, mixed $value): bool
    {
        return \curl_setopt($this->curl, $option, $value);
    }

    /** @codeCoverageIgnore */
    protected function _curl_exec(): string|bool
    {
        return \curl_exec($this->curl);
    }

    /** @codeCoverageIgnore */
    protected function _curl_error(): string
    {
        return \curl_error($this->curl);
    }

    #endregion protected
}
