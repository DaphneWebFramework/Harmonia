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

    #endregion public
}
