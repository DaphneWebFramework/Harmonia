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
    private RequestMethod|null|false $method;
    private CString|null|false $path;
    private ?CArray $queryParams;
    private ?CArray $formParams;
    private ?CArray $files;
    private ?CArray $cookies;
    private ?CArray $headers;
    private CString|null|false $body;

    /**
     * Constructs a new instance.
     *
     * Note that some properties are initialized to `false` instead of `null`.
     * This is to distinguish between an uncached state (`false`) and a cached
     * result that explicitly failed to retrieve valid data (`null`).
     *
     * As a rule, if a resolving function can fail (i.e., return `null`), its
     * corresponding property uses `false` to indicate that it has not been
     * cached yet.
     */
    protected function __construct()
    {
        $this->method = false;
        $this->path = false;
        $this->queryParams = null;
        $this->formParams = null;
        $this->files = null;
        $this->cookies = null;
        $this->headers = null;
        $this->body = false;
    }

    #region public -------------------------------------------------------------

    /**
     * Retrieves the HTTP request method.
     *
     * PHPUnit 12 introduced a restriction preventing the use of "Method" as a
     * method name. To comply with this, "Method_" was chosen instead.
     *
     * @return ?RequestMethod
     *   The request method, or `null` if the request method is not available or
     *   unsupported.
     */
    public function Method_(): ?RequestMethod
    {
        if ($this->method === false) {
            $this->method = self::resolveMethod();
        }
        return $this->method;
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
        if ($this->path === false) {
            $this->path = self::resolvePath();
        }
        return $this->path;
    }

    /**
     * Retrieves the query parameters from the `$_GET` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the query parameters.
     */
    public function QueryParams(): CArray
    {
        if ($this->queryParams === null) {
            $this->queryParams = new CArray($_GET);
        }
        return $this->queryParams;
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
        if ($this->formParams === null) {
            $this->formParams = new CArray($_POST);
        }
        return $this->formParams;
    }

    /**
     * Retrieves the uploaded files from the `$_FILES` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the uploaded files.
     */
    public function Files(): CArray
    {
        if ($this->files === null) {
            $this->files = new CArray($_FILES);
        }
        return $this->files;
    }

    /**
     * Retrieves the cookies from the `$_COOKIE` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the cookies.
     */
    public function Cookies(): CArray
    {
        if ($this->cookies === null) {
            $this->cookies = new CArray($_COOKIE);
        }
        return $this->cookies;
    }

    /**
     * Retrieves the HTTP headers.
     *
     * If `apache_request_headers()` is available, it is used to fetch the
     * headers. Otherwise, headers are retrieved from the `$_SERVER` superglobal.
     *
     * All header names are converted to lowercase to ensure predictable
     * case-sensitive lookups.
     *
     * @return CArray
     *   A `CArray` instance where the keys are lowercase header names and the
     *   values are their respective header values.
     */
    public function Headers(): CArray
    {
        if ($this->headers === null) {
            $this->headers = self::resolveHeaders();
        }
        return $this->headers;
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
        if ($this->body === false) {
            $this->body = self::resolveBody();
        }
        return $this->body;
    }

    #endregion public

    #region private ------------------------------------------------------------

    private static function resolveMethod(): ?RequestMethod
    {
        $requestMethod = Server::Instance()->RequestMethod();
        if ($requestMethod === null) {
            return null;
        }
        return RequestMethod::tryFrom((string)$requestMethod->UppercaseInPlace());
    }

    private static function resolvePath(): ?CString
    {
        $requestUri = Server::Instance()->RequestUri();
        if ($requestUri === null) {
            return null;
        }
        $match = $requestUri->Match('^([^?#]+)');
        if ($match === null) {
            return null;
        }
        return new CString(\rtrim($match[1], '/'));
    }

    private static function resolveHeaders(): CArray
    {
        if (\function_exists('apache_request_headers')) {
            return new CArray(
                \array_change_key_case(
                    \apache_request_headers(),
                    CASE_LOWER
                )
            );
        } else {
            return Server::Instance()->RequestHeaders();
        }
    }

    private static function resolveBody(): ?CString
    {
        $data = \file_get_contents('php://input');
        if ($data === false) {
            return null;
        }
        return new CString($data);
    }

    #endregion private
}
