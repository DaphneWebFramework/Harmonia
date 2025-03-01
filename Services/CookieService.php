<?php declare(strict_types=1);
/**
 * CookieService.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Services;

use \Harmonia\Patterns\Singleton;

use \Harmonia\Server;

/**
 * Provides cookie management services.
 */
class CookieService extends Singleton
{
    #region public -------------------------------------------------------------

    /**
     * Adds or updates a cookie.
     *
     * @param string $name
     *   The cookie name.
     * @param string|false $value
     *   The cookie value. If `false`, the cookie is deleted.
     * @return bool
     *   Returns `true` if the cookie is set successfully. Returns `false` if
     *   the HTTP headers have already been sent or if the cookie could not be
     *   set for any other reason.
     */
    public function SetCookie(string $name, string|false $value): bool
    {
        if ($this->_headers_sent()) {
            return false;
        }
        return $this->_setcookie($name, $value, [
            'expires'  => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => Server::Instance()->IsSecure(),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    /**
     * Deletes a cookie.
     *
     * This is a convenience method that calls `SetCookie` with the value set to
     * `false`.
     *
     * @param string $name
     *   The cookie name.
     * @return bool
     *   Returns `true` if the cookie is deleted successfully. Returns `false`
     *   if the HTTP headers have already been sent or if the cookie could not
     *   be deleted for any other reason.
     *
     * @see SetCookie
     */
    public function DeleteCookie(string $name): bool
    {
        return $this->SetCookie($name, false);
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _headers_sent(): bool
    {
        return \headers_sent();
    }

    /** @codeCoverageIgnore */
    protected function _setcookie(string $name, string|false $value,
        array $options): bool
    {
        return \setcookie($name, $value, $options);
    }

    #endregion protected
}
