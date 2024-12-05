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
     * Stores server environment data from the `$_SERVER` superglobal.
     *
     * @var CArray
     */
    private readonly CArray $data;

    /**
     * Constructs a new instance by initializing the server environment data.
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
     *   returns `false`.
     */
    public function IsSecure(): bool
    {
        return \in_array($this->data->Get('HTTPS'), ['on', '1'], true)
            || $this->data->Get('SERVER_PORT') === '443'
            || $this->data->Get('REQUEST_SCHEME') === 'https'
            || $this->data->Get('HTTP_X_FORWARDED_PROTO') === 'https';
    }

    /**
     * Returns the hostname.
     *
     * @return string
     *   The server name as defined by the `SERVER_NAME` key in the `$_SERVER`
     *   superglobal. Returns an empty string if the key does not exist.
     */
    public function HostName(): string
    {
        return $this->data->GetOrDefault('SERVER_NAME', '');
    }

    /**
     * Returns the full server URL, including the protocol and hostname.
     *
     * @return string
     *   The full URL constructed from the protocol (http/https) and the server
     *   name.
     */
    public function HostUrl(): string
    {
        return ($this->IsSecure() ? 'https' : 'http') . '://' . $this->HostName();
    }

    /**
     * Returns the root directory path.
     *
     * @return string
     *   The directory path as defined by the `DOCUMENT_ROOT` key in the
     *   `$_SERVER` superglobal, e.g., "C:/xampp/htdocs". Returns an empty
     *   string if the key does not exist.
     */
    public function RootDirectory(): string
    {
        return $this->data->GetOrDefault('DOCUMENT_ROOT', '');
    }

    #endregion public
}
