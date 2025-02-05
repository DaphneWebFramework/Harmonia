<?php declare(strict_types=1);
/**
 * Session.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia;

use \Harmonia\Patterns\Singleton;

/**
 * Manages PHP session lifecycle and data access.
 */
class Session extends Singleton
{
    /**
     * Constructs a new instance by configuring the session for security.
     */
    protected function __construct()
    {
        // Enforces strict session ID validation. Prevents PHP from accepting
        // uninitialized or invalid session IDs from `$_GET`, `$_POST`, and
        // `$_COOKIE`. This mitigates session fixation attacks.
        \ini_set('session.use_strict_mode', '1');

        // Ensures that session IDs are only stored in cookies. This prevents
        // session IDs from being included in URL parameters, reducing exposure
        // to session hijacking.
        \ini_set('session.use_cookies', '1');

        // Disables the use of URL-based session IDs (`session.use_trans_sid`
        // must also be 0). This ensures session IDs are exclusively managed via
        // cookies.
        \ini_set('session.use_only_cookies', '1');

        // Disables PHP’s transparent session ID management. Prevents PHP from
        // appending session IDs to URLs, which could expose them in logs.
        \ini_set('session.use_trans_sid', '0');

        // Prevents caching of session data. This ensures that sensitive session
        // information is not stored in the browser’s cache.
        \ini_set('session.cache_limiter', 'nocache');

        // Configure the session cookie parameters to enhance security.
        \session_set_cookie_params([
            // Session expires when the browser is closed, as no expiration time
            // is set.
            'lifetime' => 0,
            // Makes the session cookie available for all paths on the domain.
            'path' => '/',
            // Restricts the session cookie to the exact domain without allowing
            // subdomains.
            'domain' => '',
            // If `true`, ensures cookies are sent only over HTTPS.
            'secure' => Server::Instance()->IsSecure(),
            // Prevents JavaScript from accessing the session cookie.
            'httponly' => true,
            // Prevents the session cookie from being sent with cross-site
            // requests, mitigating cross-site request forgery (CSRF) attacks.
            'samesite' => 'Strict'
        ]);
    }

    #region public -------------------------------------------------------------

    /**
     * Checks whether a session has been started.
     *
     * @return bool
     *   Returns `true` if the session has been started, `false` otherwise.
     */
    public function IsStarted(): bool
    {
        return $this->_session_status() === \PHP_SESSION_ACTIVE;
    }

    /**
     * Retrieves the current session name.
     *
     * The session name is used as the cookie name for storing the session ID.
     *
     * @return string
     *   The current session name.
     * @throws \RuntimeException
     *   If retrieving the session name fails.
     */
    public function Name(): string
    {
        return $this->_session_name();
    }

    /**
     * Starts a new session or resumes an existing one.
     *
     * @throws \RuntimeException
     *   If starting the session fails.
     */
    public function Start(): void
    {
        $this->_session_start();
    }

    /**
     * Saves session data and closes the session.
     *
     * @throws \RuntimeException
     *   If writing and closing the session fails.
     */
    public function Close(): void
    {
        $this->_session_write_close();
    }

    /**
     * Sets a session variable.
     *
     * @param string $key
     *   The name of the session variable.
     * @param mixed $value
     *   The value of the session variable.
     */
    public function Set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a session variable.
     *
     * @param string $key
     *   The name of the session variable.
     * @param mixed $defaultValue
     *   (Optional) The default value to return if the session variable does not
     *   exist. Defaults to `null`.
     * @return mixed
     *   The value of the session variable if it exists, the default value
     *   otherwise.
     */
    public function Get(string $key, mixed $defaultValue = null): mixed
    {
        if (!isset($_SESSION)) {
            return $defaultValue;
        }
        if (!\array_key_exists($key, $_SESSION)) {
            return $defaultValue;
        }
        return $_SESSION[$key];
    }

    /**
     * Removes a session variable.
     *
     * @param string $key
     *   The name of the session variable.
     */
    public function Remove(string $key): void
    {
        if (isset($_SESSION)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clears all session variables.
     *
     * @throws \RuntimeException
     *   If clearing session data fails.
     */
    public function Clear(): void
    {
        if ($this->IsStarted()) {
            $this->_session_unset();
        } else {
            $_SESSION = [];
        }
    }

    /**
     * Destroys the current session.
     *
     * This method clears session data and completely destroys the session.
     * Before calling this method, ensure that the session cookie is deleted
     * using `Name()` to retrieve the session cookie name.
     *
     * @throws \RuntimeException
     *   If destroying the session fails.
     *
     * @see Name
     */
    public function Destroy(): void
    {
        $this->Clear();
        $this->_session_destroy();
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _session_status(): int
    {
        return \session_status();
    }

    /** @codeCoverageIgnore */
    protected function _session_name(?string $name = null): string
    {
        if ($name === null) {
            $currentName = \session_name();
            if ($currentName === false) {
                throw new \RuntimeException('Failed to get session name.');
            }
            return $currentName;
        } else {
            $oldName = \session_name($name);
            if ($oldName === false) {
                throw new \RuntimeException('Failed to set session name.');
            }
            return $oldName;
        }
    }

    /** @codeCoverageIgnore */
    protected function _session_start(): void
    {
        if (!\session_start()) {
            throw new \RuntimeException('Failed to start session.');
        }
    }

    /** @codeCoverageIgnore */
    protected function _session_write_close(): void
    {
        if (!\session_write_close()) {
            throw new \RuntimeException('Failed to write and close session.');
        }
    }

    /** @codeCoverageIgnore */
    protected function _session_unset(): void
    {
        if (!\session_unset()) {
            throw new \RuntimeException('Failed to unset session.');
        }
    }

    /** @codeCoverageIgnore */
    protected function _session_destroy(): void
    {
        if (!\session_destroy()) {
            throw new \RuntimeException('Failed to destroy session.');
        }
    }

    #endregion protected
}
