<?php declare(strict_types=1);
/**
 * Response.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Http;

use \Harmonia\Core\CArray;
use \Harmonia\Services\CookieService;

/**
 * Represents an HTTP response.
 */
class Response
{
    /**
     * Stores the HTTP status code.
     *
     * @var StatusCode
     */
    private StatusCode $statusCode;

    /**
     * Stores the HTTP headers.
     *
     * @var ?CArray
     */
    private ?CArray $headers;

    /**
     * Stores the cookies.
     *
     * @var ?CArray
     */
    private ?CArray $cookies;

    /**
     * Stores the response body.
     *
     * @var ?string
     */
    private ?string $body;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->statusCode = StatusCode::OK;
        $this->headers = null;
        $this->cookies = null;
        $this->body = null;
    }

    /**
     * Sets the HTTP status code.
     *
     * @param StatusCode $statusCode
     *   The HTTP status code.
     * @return self
     *   The current instance.
     */
    public function SetStatusCode(StatusCode $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Adds or updates an HTTP header.
     *
     * @param string $name
     *   The header name.
     * @param string $value
     *   The header value.
     * @return self
     *   The current instance.
     */
    public function SetHeader(string $name, string $value): self
    {
        if ($this->headers === null) {
            $this->headers = new CArray();
        }
        $this->headers->Set($name, $value);
        return $this;
    }

    /**
     * Adds or updates a cookie.
     *
     * @param string $name
     *   The cookie name.
     * @param string $value
     *   The cookie value. If empty, the cookie is deleted.
     * @return self
     *   The current instance.
     */
    public function SetCookie(string $name, string $value): self
    {
        if ($this->cookies === null) {
            $this->cookies = new CArray();
        }
        $this->cookies->Set($name, $value);
        return $this;
    }

    /**
     * Deletes a cookie.
     *
     * This is a convenience method that calls `SetCookie` with an empty value.
     *
     * @param string $name
     *   The cookie name.
     * @return self
     *   The current instance.
     */
    public function DeleteCookie(string $name): self
    {
        return $this->SetCookie($name, '');
    }

    /**
     * Sets the response body.
     *
     * @param string|\Stringable $body
     *   The response body.
     * @return self
     *   The current instance.
     */
    public function SetBody(string|\Stringable $body): self
    {
        $this->body = (string)$body;
        return $this;
    }

    /**
     * Sends the response.
     */
    public function Send(): void
    {
        if ($this->canSendHeaders()) {
            $this->sendStatusCode();
            if ($this->headers !== null) {
                foreach ($this->headers as $name => $value) {
                    $this->sendHeader($name, $value);
                }
            }
            if ($this->cookies !== null) {
                $cookieService = CookieService::Instance();
                foreach ($this->cookies as $name => $value) {
                    $cookieService->SetCookie($name, $value);
                }
            }
        }
        if ($this->body !== null) {
            $this->sendBody();
        }
    }

    /**
     * Redirects the client to a new URL.
     *
     * @param string $url
     *   The URL to redirect to.
     * @param bool $exitScript
     *   (Optional) If `true`, the script will exit after sending the response.
     *   Default is `true`.
     */
    public function Redirect(string|\Stringable $url, bool $exitScript = true): void
    {
        $this->SetStatusCode(StatusCode::Found)
             ->SetHeader('Location', (string)$url)
             ->Send();
        if ($exitScript) {
            $this->exitScript();
        }
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function canSendHeaders(): bool
    {
        return \headers_sent() === false;
    }

    /** @codeCoverageIgnore */
    protected function sendStatusCode(): void
    {
        \http_response_code($this->statusCode->value);
    }

    /** @codeCoverageIgnore */
    protected function sendHeader(string $name, string $value): void
    {
        \header("{$name}: {$value}");
    }

    /** @codeCoverageIgnore */
    protected function sendBody(): void
    {
        echo $this->body;
    }

    /** @codeCoverageIgnore */
    protected function exitScript(): void
    {
        exit();
    }

    #endregion protected
}
