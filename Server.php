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
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;

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
     * @return CUrl
     *   A new `CUrl` instance representing the root URL (e.g., "http://localhost"
     *   or "https://example.com").
     * @throws \RuntimeException
     *   If the server name is not set.
     */
    public function Url(): CUrl
    {
        $serverName = $this->superglobal->GetOrDefault('SERVER_NAME', '');
        if ($serverName === '') {
            throw new \RuntimeException('Server name is not set.');
        }
        return CUrl::Join($this->IsSecure() ? 'https://' : 'http://', $serverName);
    }

    /**
     * Retrieves the web server's root directory path.
     *
     * @return CPath
     *   A new `CPath` instance representing the root directory path (e.g.,
     *   "C:/xampp/htdocs" or "/var/www/html").
     * @throws \RuntimeException
     *   If the document root is not set.
     */
    public function Path(): CPath
    {
        $documentRoot = $this->superglobal->GetOrDefault('DOCUMENT_ROOT', '');
        if ($documentRoot === '') {
            throw new \RuntimeException('Document root is not set.');
        }
        return new CPath($documentRoot);
    }

    #endregion public
}
