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
 * Performs HTTP requests.
 */
class Client
{
    private ?\CurlHandle $curl;
    private readonly \stdClass $request;
    private readonly \stdClass $response;
    private readonly \stdClass $lastError;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * The request method is initialized to "GET" by default.
     *
     * @throws \RuntimeException
     *   If the transport layer fails to initialize.
     */
    public function __construct()
    {
        $curl = $this->_curl_init();
        if ($curl === false) {
            throw new \RuntimeException("Failed to initialize transport layer.");
        }
        $this->curl = $curl;
        $this->request = new \stdClass();
        $this->Clear();
        $this->response = new \stdClass();
        $this->clearResponse();
        $this->lastError = new \stdClass();
        $this->clearLastError();
    }

    /**
     * Releases the underlying transport resources.
     */
    public function __destruct()
    {
        if (!isset($this->curl)) {
            return;
        }
        if (\PHP_VERSION_ID < 80500) {
            $this->_curl_close();
        }
        $this->curl = null;
    }

    /**
     * Clears only the request fields back to defaults.
     *
     * @return self
     *   The current instance.
     */
    public function Clear(): self
    {
        $this->request->method  = 'GET';
        $this->request->url     = '';
        $this->request->headers = [];
        $this->request->body    = '';
        return $this;
    }

    /**
     * Sets the request method.
     *
     * PHPUnit 12 introduced a restriction preventing the use of "Method" as a
     * method name. To comply with this, "Method_" was chosen instead.
     *
     * @param string $method
     *   The method to set.
     * @return self
     *   The current instance.
     */
    public function Method_(string $method): self
    {
        $this->request->method = \strtoupper($method);
        return $this;
    }

    /**
     * Sets the request method to "GET".
     *
     * @return self
     *   The current instance.
     *
     * @see Post
     * @see Method_
     */
    public function Get(): self
    {
        $this->request->method = 'GET';
        return $this;
    }

    /**
     * Sets the request method to "POST".
     *
     * @return self
     *   The current instance.
     *
     * @see Get
     * @see Method_
     */
    public function Post(): self
    {
        $this->request->method = 'POST';
        return $this;
    }

    /**
     * Sets the request URL.
     *
     * @param string $url
     *   The URL to set.
     * @return self
     *   The current instance.
     */
    public function Url(string $url): self
    {
        $this->request->url = $url;
        return $this;
    }

    /**
     * Sets the request headers or returns the response headers.
     *
     * @param array<string,string>|null $headers
     *   Associative array of header name/value pairs to set, or `null` to get
     *   the response headers.
     * @return self|array<string,string>
     *   The current instance when setting, or an associative array of response
     *   headers when getting. Response headers are returned in lowercase keys.
     */
    public function Headers(?array $headers = null): self|array
    {
        if ($headers !== null) {
            $this->request->headers = $headers;
            return $this;
        }
        return $this->response->headers;
    }

    /**
     * Sets the request body or returns the response body.
     *
     * When passing a non‑empty string, the default `Content-Type` header will
     * be `application/x-www-form-urlencoded` unless you explicitly set your own
     * (e.g., `application/json`, `application/xml`). Passing an empty string
     * results in no payload being sent.
     *
     * When passing an array, it will always be encoded as `multipart/form-data`
     * with an automatically generated boundary. To send URL‑encoded form data,
     * you must encode the array yourself (e.g., with `http_build_query()`) and
     * pass the resulting string instead. Passing an empty array results in no
     * payload being sent.
     *
     * @param string|array|null $body
     *   The body to set, or `null` to get the response body.
     * @return self|string
     *   The current instance when setting, or the response body when getting.
     */
    public function Body(string|array|null $body = null): self|string
    {
        if ($body !== null) {
            $this->request->body = $body;
            return $this;
        }
        return $this->response->body;
    }

    /**
     * Returns the response status code.
     *
     * @return int
     *   The response status code.
     */
    public function StatusCode(): int
    {
        return $this->response->statusCode;
    }

    /**
     * Executes the request and populates the response.
     *
     * @return bool
     *   Returns `true` on success, or `false` if a transport error occurred.
     */
    public function Send(): bool
    {
        $this->reset();
        $this->applyRequestMethod();
        $this->applyRequestUrl();
        $this->applyRequestHeaders();
        $this->applyRequestBody();
        $this->attachResponseHeaderHandler();
        $result = $this->execute();
        if ($result === false) {
            return false;
        }
        $this->updateResponseStatusCode();
        $this->updateResponseBody($result);
        return true;
    }

    /**
     * Returns the details of the last transport error.
     *
     * @return \stdClass
     *   Object with `code` (int) and `message` (string).
     */
    public function LastError(): \stdClass
    {
        return $this->lastError;
    }

    #endregion public

    #region protected ----------------------------------------------------------

    protected function clearResponse(): void
    {
        $this->response->statusCode = 0;
        $this->response->headers    = [];
        $this->response->body       = '';
    }

    protected function clearLastError(): void
    {
        $this->lastError->code    = 0;
        $this->lastError->message = '';
    }

    protected function reset(): void
    {
        $this->_curl_reset();
        $this->_curl_setopt(\CURLOPT_RETURNTRANSFER, true);
        $this->clearResponse();
        $this->clearLastError();
    }

    protected function applyRequestMethod(): void
    {
        $this->_curl_setopt(\CURLOPT_CUSTOMREQUEST, $this->request->method);
    }

    protected function applyRequestUrl(): void
    {
        $this->_curl_setopt(\CURLOPT_URL, $this->request->url);
    }

    protected function applyRequestHeaders(): void
    {
        if (\count($this->request->headers) === 0) {
            return;
        }
        $headers = [];
        foreach ($this->request->headers as $name => $value) {
            $headers[] = "$name: $value";
        }
        $this->_curl_setopt(\CURLOPT_HTTPHEADER, $headers);
    }

    protected function applyRequestBody(): void
    {
        if ($this->request->body === '' || $this->request->body === []) {
            return;
        }
        $this->_curl_setopt(\CURLOPT_POSTFIELDS, $this->request->body);
    }

    protected function attachResponseHeaderHandler(): void
    {
        $this->_curl_setopt(\CURLOPT_HEADERFUNCTION, function($_, string $header) {
            $pair = \explode(':', $header, 2);
            if (\count($pair) === 2) {
                $name  = \strtolower(\trim($pair[0]));
                $value = \trim($pair[1]);
                $this->response->headers[$name] = $value;
            }
            return \strlen($header);
        });
    }

    protected function execute(): string|false
    {
        $result = $this->_curl_exec();
        if ($result === false) {
            $this->lastError->code = $this->_curl_errno();
            $this->lastError->message = $this->_curl_error();
            return false;
        }
        return $result;
    }

    protected function updateResponseStatusCode(): void
    {
        $this->response->statusCode = $this->_curl_getinfo(CURLINFO_HTTP_CODE);
    }

    protected function updateResponseBody($body): void
    {
        $this->response->body = $body;
    }

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
    protected function _curl_reset(): void
    {
        \curl_reset($this->curl);
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
    protected function _curl_getinfo(?int $option = null): mixed
    {
        return \curl_getinfo($this->curl, $option);
    }

    /** @codeCoverageIgnore */
    protected function _curl_errno(): int
    {
        return \curl_errno($this->curl);
    }

    /** @codeCoverageIgnore */
    protected function _curl_error(): string
    {
        return \curl_error($this->curl);
    }

    #endregion protected
}
