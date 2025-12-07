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
use \Harmonia\Patterns\CachedValue;
use \Harmonia\Server;

/**
 * Represents an HTTP request.
 */
class Request extends Singleton
{
    private CachedValue $method;
    private CachedValue $path;
    private CachedValue $queryParams;
    private CachedValue $formParams;
    private CachedValue $files;
    private CachedValue $cookies;
    private CachedValue $headers;
    private CachedValue $body;

    /**
     * Constructs a new instance.
     *
     * All values are initialized for lazy evaluation and will be resolved
     * on first access through their respective getter methods.
     */
    protected function __construct()
    {
        $this->method = new CachedValue();
        $this->path = new CachedValue();
        $this->queryParams = new CachedValue();
        $this->formParams = new CachedValue();
        $this->files = new CachedValue();
        $this->cookies = new CachedValue();
        $this->headers = new CachedValue();
        $this->body = new CachedValue();
    }

    #region public -------------------------------------------------------------

    /**
     * Retrieves the HTTP request method.
     *
     * PHPUnit 12 introduced a restriction preventing the use of "Method" as a
     * method name. To comply with this, "Method_" was chosen instead.
     *
     * @return ?CString
     *   The request method, or `null` if the request method is not available.
     */
    public function Method_(): ?CString
    {
        return $this->method->Get(function() {
            $requestMethod = Server::Instance()->RequestMethod();
            if ($requestMethod === null) {
                return null;
            }
            return $requestMethod->UppercaseInPlace();
        });
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
        return $this->path->Get(function() {
            $requestUri = Server::Instance()->RequestUri();
            if ($requestUri === null) {
                return null;
            }
            $match = $requestUri->Match('^([^?#]+)');
            if ($match === null) {
                return null;
            }
            return new CString(\rtrim($match[1], '/'));
        });
    }

    /**
     * Retrieves the query parameters from the `$_GET` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the query parameters.
     */
    public function QueryParams(): CArray
    {
        return $this->queryParams->Get(fn() => new CArray($_GET));
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
        return $this->formParams->Get(fn() => new CArray($_POST));
    }

    /**
     * Retrieves the uploaded files from the `$_FILES` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the uploaded files.
     */
    public function Files(): CArray
    {
        return $this->files->Get(fn() => new CArray($_FILES));
    }

    /**
     * Retrieves the cookies from the `$_COOKIE` superglobal.
     *
     * @return CArray
     *   A `CArray` instance containing the cookies.
     */
    public function Cookies(): CArray
    {
        return $this->cookies->Get(fn() => new CArray($_COOKIE));
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
        return $this->headers->Get(function() {
            if (\function_exists('apache_request_headers')) {
                return new CArray(
                    \array_change_key_case(
                        \apache_request_headers(),
                        \CASE_LOWER
                    )
                );
            } else {
                return Server::Instance()->RequestHeaders();
            }
        });
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
     * @return ?string
     *   The raw body content, or `null` if an error occurs.
     */
    public function Body(): ?string
    {
        return $this->body->Get(function() {
            $data = \file_get_contents('php://input');
            if ($data === false) {
                return null;
            }
            return $data;
        });
    }

    /**
     * Decodes the request body as JSON and returns it as an associative array.
     *
     * @return array
     *   The decoded JSON data, or an empty array if the request's media type
     *   is not `application/json`, if the body content cannot be read, or if
     *   the JSON is invalid.
     */
    public function JsonBody(): array
    {
        if (!$this->IsMediaType('application/json')) {
            return [];
        }
        $body = $this->Body();
        if ($body === null) {
            return [];
        }
        $decoded = \json_decode($body, true);
        if (!\is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    /**
     * Checks whether the request's media type matches the given type.
     *
     * @param string $expectedType
     *   The expected media type (e.g., 'application/json').
     * @return bool
     *   Return `true` if the request's media type matches, `false` otherwise.
     */
    public function IsMediaType(string $expectedType): bool
    {
        $header = $this->Headers()->Get('content-type');
        if (!\is_string($header)) {
            return false;
        }
        foreach (\explode(';', $header) as $part) {
            if (0 === \strcasecmp(\trim($part), $expectedType)) {
                return true;
            }
        }
        return false;
    }

    #endregion public
}
