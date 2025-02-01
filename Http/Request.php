<?php declare(strict_types=1);
/**
 * Request.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Http;

use \Harmonia\Patterns\Singleton;

use \Harmonia\Core\CArray;
use \Harmonia\Core\CString;
use \Harmonia\Server;

/**
 * Represents an HTTP request.
 */
class Request extends Singleton
{
    /**
     * Stores the server instance used to obtain request-related data.
     *
     * @var Server
     */
    private Server $server;

    /**
     * Constructs a new instance.
     */
    protected function __construct()
    {
        $this->server = Server::Instance();
    }

    #region public -------------------------------------------------------------

    /**
     * Retrieves the HTTP request method.
     *
     * @return ?RequestMethod
     *   The request method, or `null` if the request method is not available or
     *   unsupported.
     */
    public function Method(): ?RequestMethod
    {
        $requestMethod = $this->server->RequestMethod();
        if ($requestMethod === null) {
            return null;
        }
        return RequestMethod::tryFrom((string)$requestMethod->UppercaseInPlace());
    }

    /**
     * Retrieves the path component of the request URI.
     *
     * This method extracts the path from the request URI, removing any query
     * strings (`?query=value`) or fragments (`#fragment`). The trailing slash
     * is also trimmed for consistency.
     *
     * @return ?CString
     *   The request path, or `null` if the request path is not available.
     */
    public function Path(): ?CString
    {
        $requestUri = $this->server->RequestUri();
        if ($requestUri === null) {
            return null;
        }
        $match = $requestUri->Match('^([^?#]+)');
        if ($match === null) {
            return null;
        }
        return new CString(\rtrim($match[1], '/'));
    }

    /**
     * Retrieves the query parameters from the `$_GET` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the query parameters.
     */
    public function QueryParams(): CArray
    {
        return new CArray($_GET);
    }

    /**
     * Retrieves the form parameters from the `$_POST` superglobal.
     *
     * The form parameters are populated when the request method is `POST` and
     * the `Content-Type` header is either `application/x-www-form-urlencoded`
     * or `multipart/form-data`.
     *
     * @return CArray
     *   A `CArray` instance containing the form parameters.
     */
    public function FormParams(): CArray
    {
        return new CArray($_POST);
    }

    /**
     * Retrieves the uploaded files from the `$_FILES` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the uploaded files.
     */
    public function Files(): CArray
    {
        return new CArray($_FILES);
    }

    /**
     * Retrieves the cookies from the `$_COOKIE` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the cookies.
     */
    public function Cookies(): CArray
    {
        return new CArray($_COOKIE);
    }

    /**
     * Retrieves the HTTP headers.
     *
     * This method obtains headers using `apache_request_headers()` if available.
     * Otherwise, it emulates the behavior by extracting headers from `$_SERVER`,
     * specifically those prefixed with 'HTTP_'.
     *
     * @return CArray
     *   A `CArray` instance containing the HTTP headers with keys formatted in
     *   title case with hyphens (e.g., "User-Agent", "Content-Type").
     */
    public function Headers(): CArray
    {
        if (\function_exists('apache_request_headers')) {
            return new CArray(\apache_request_headers());
        } else {
            return $this->server->RequestHeaders();
        }
    }

    /**
     * Retrieves the raw request body content.
     *
     * This method reads and returns the raw body content (payload) directly
     * from the `php://input` stream. It provides the unprocessed request data,
     * which can include various formats like JSON, XML, or binary content.
     *
     * Be aware that `php://input` is not available in POST requests with a
     * `Content-Type` of `multipart/form-data`, as PHP processes these requests
     * differently. If raw input is required for file uploads, consider using
     * the PUT method instead.
     *
     * @return ?CString
     *   A `CString` instance containing the raw body data, or `null` if an
     *   error occurs.
     */
    public function Body(): ?CString
    {
        $data = \file_get_contents('php://input');
        if ($data === false) {
            return null;
        }
        return new CString($data);
    }

    #endregion public
}
