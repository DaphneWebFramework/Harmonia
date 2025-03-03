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

use \Harmonia\Config;
use \Harmonia\Server;

/**
 * Provides cookie management services.
 */
class CookieService extends Singleton
{
    /**
     * The default application name used for cookie naming.
     *
     * If "AppName" is not set in the configuration, this value is used.
     */
    private const DEFAULT_APP_NAME = 'Harmonia';

    #region public -------------------------------------------------------------

    /**
     * Adds or updates a cookie.
     *
     * @param string $name
     *   The cookie name.
     * @param string $value
     *   The cookie value. If empty, the cookie is deleted.
     * @return bool
     *   Returns `true` if the cookie is set successfully. Returns `false` if
     *   the HTTP headers have already been sent or if the cookie could not be
     *   set for any other reason.
     */
    public function SetCookie(string $name, string $value): bool
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
     * This is a convenience method that calls `SetCookie` with an empty value.
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
        return $this->SetCookie($name, '');
    }

    /**
     * Generates an application-specific cookie name combined with the given
     * suffix.
     *
     * The application name is retrieved from the configuration. If no
     * application name is set, a default value is used.
     *
     * @param string $suffix
     *   The suffix to append to the application name. This value cannot be
     *   empty.
     * @return string
     *   The generated cookie name, always in uppercase and following the
     *   `{APPNAME}_{SUFFIX}` format.
     * @throws \InvalidArgumentException
     *   If the suffix is empty.
     */
    public function GenerateCookieName(string $suffix): string
    {
        if ($suffix === '') {
            throw new \InvalidArgumentException('Suffix cannot be empty.');
        }
        $appName = Config::Instance()->OptionOrDefault('AppName', '');
        if ($appName === '') {
            $appName = self::DEFAULT_APP_NAME;
        }
        return \strtoupper("{$appName}_{$suffix}");
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _headers_sent(): bool
    {
        return \headers_sent();
    }

    /** @codeCoverageIgnore */
    protected function _setcookie(string $name, string $value, array $options): bool
    {
        return \setcookie($name, $value, $options);
    }

    #endregion protected
}
