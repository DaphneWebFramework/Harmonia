<?php declare(strict_types=1);
/**
 * Server.php
 *
 * (C) 2024 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia;

use \Harmonia\Patterns\Singleton;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CString;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;

/**
 * Provides structured access to server environment data.
 */
class Server extends Singleton
{
    /**
     * Stores the server environment data from `$_SERVER`.
     *
     * @var CArray
     */
    private readonly CArray $data;

    /**
     * Constructs a new instance by loading the server environment data.
     */
    protected function __construct()
    {
        $this->data = new CArray($_SERVER);
    }

    #region public -------------------------------------------------------------

    /**
     * Checks if the connection is secure.
     *
     * @return bool
     *   Returns `true` if the connection is secure (e.g., HTTPS); otherwise,
     *   `false`.
     */
    public function IsSecure(): bool
    {
        return \in_array($this->data->Get('HTTPS'), ['on', '1'], true)
            || $this->data->Get('SERVER_PORT') === '443'
            || $this->data->Get('REQUEST_SCHEME') === 'https'
            || $this->data->Get('HTTP_X_FORWARDED_PROTO') === 'https';
    }

    /**
     * Retrieves the web server's root URL, including the protocol and host name
     * or IP address.
     *
     * @return ?CUrl
     *   A `CUrl` instance representing the root URL (e.g., "http://localhost"
     *   or "https://example.com"), or `null` if the server name is not set.
     */
    public function Url(): ?CUrl
    {
        $serverName = $this->data->GetOrDefault('SERVER_NAME', '');
        if ($serverName === '') {
            return null;
        }
        return CUrl::Join($this->IsSecure() ? 'https://' : 'http://', $serverName);
    }

    /**
     * Retrieves the web server's root directory path.
     *
     * @return ?CPath
     *   A `CPath` instance representing the root directory path (e.g.,
     *   "C:/xampp/htdocs" or "/var/www/html"), or `null` if the document root
     *   is not set.
     */
    public function Path(): ?CPath
    {
        $documentRoot = $this->data->GetOrDefault('DOCUMENT_ROOT', '');
        if ($documentRoot === '') {
            return null;
        }
        return new CPath($documentRoot);
    }

    /**
     * Retrieves the request method.
     *
     * @return ?CString
     *   A `CString` instance representing the request method (e.g., "GET",
     *  "POST", "PUT", "DELETE"), or `null` if the request method is not set.
     */
    public function RequestMethod(): ?CString
    {
        $requestMethod = $this->data->GetOrDefault('REQUEST_METHOD', '');
        if ($requestMethod === '') {
            return null;
        }
        return new CString($requestMethod);
    }

    /**
     * Retrieves the request URI.
     *
     * The request URI is the part of the URL that comes after the domain name,
     * including the query string and fragment identifier.
     *
     * @return ?CString
     *   A `CString` instance representing the request URI (e.g., "/index.php",
     *   "/index.php?foo=bar#section"), or `null` if the request URI is not set.
     */
    public function RequestUri(): ?CString
    {
        $requestUri = $this->data->GetOrDefault('REQUEST_URI', '');
        if ($requestUri === '') {
            return null;
        }
        return new CString($requestUri);
    }

    /**
     * Retrieves the HTTP headers from the request.
     *
     * This method extracts headers from `$_SERVER`, including those prefixed
     * with 'HTTP_' and standard headers such as 'CONTENT_TYPE' and 'CONTENT_LENGTH'.
     *
     * All header names are converted to lowercase, with underscores (`_`)
     * replaced by hyphens (`-`). Header values remain unchanged.
     *
     * @return CArray
     *   A `CArray` instance where the keys are formatted header names and
     *   the values are their respective header values.
     */
    public function RequestHeaders(): CArray
    {
        $headers = new CArray();
        foreach ($this->data as $name => $value) {
            if (\str_starts_with($name, 'HTTP_')) {
                $headers->Set($this->formatHeaderName(\substr($name, 5)),
                              $value);
            }
        }
        foreach (['CONTENT_TYPE', 'CONTENT_LENGTH'] as $name) {
            if ($this->data->Has($name)) {
                $headers->Set($this->formatHeaderName($name),
                              $this->data->Get($name));
            }
        }
        return $headers;
    }

    /**
     * Retrieves the client's IP address.
     *
     * @return string
     *   The client's IP address.
     */
    public function ClientAddress(): string
    {
        return $this->data->GetOrDefault('REMOTE_ADDR', '');
    }

    #endregion public

    #region private ------------------------------------------------------------

    /**
     * Formats a server variable name by converting it to lowercase and replacing
     * underscores (`_`) with hyphens (`-`).
     *
     * @param string $name
     *   The raw header name (without "HTTP_" prefix).
     * @return string
     *   The formatted header name.
     */
    private function formatHeaderName(string $name): string
    {
        return \str_replace('_', '-', \strtolower($name));
    }

    #endregion private
}
