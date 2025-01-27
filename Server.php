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

/**
 * Provides structured access to server environment data.
 */
class Server extends Singleton
{
    /**
     * Stores values from the `$_SERVER` superglobal.
     *
     * @var CArray
     */
    private readonly CArray $superglobal;

    /**
     * Constructs a new instance with values from the `$_SERVER` superglobal.
     */
    protected function __construct()
    {
        $this->superglobal = new CArray($_SERVER);
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
        return \in_array($this->superglobal->Get('HTTPS'), ['on', '1'], true)
            || $this->superglobal->Get('SERVER_PORT') === '443'
            || $this->superglobal->Get('REQUEST_SCHEME') === 'https'
            || $this->superglobal->Get('HTTP_X_FORWARDED_PROTO') === 'https';
    }

    /**
     * Retrieves the web server's root URL, including the protocol and hostname.
     *
     * @return string
     *   The fully qualified URL (e.g., "http://localhost" or "https://example.com").
     */
    public function Url(): string
    {
        return ($this->IsSecure() ? 'https' : 'http') . '://'
            . $this->superglobal->GetOrDefault('SERVER_NAME', '');
    }

    /**
     * Retrieves the web server's root directory path.
     *
     * @return string
     *   The root directory path (e.g., "C:/xampp/htdocs" or "/var/www/html").
     */
    public function Path(): string
    {
        return $this->superglobal->GetOrDefault('DOCUMENT_ROOT', '');
    }

    #endregion public
}
