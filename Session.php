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

use \Harmonia\Config;
use \Harmonia\Server;
use \Harmonia\Services\CookieService;

/**
 * Manages PHP session lifecycle and data access.
 */
class Session extends Singleton
{
    /**
     * The default application name used for session naming.
     *
     * If "AppName" is not set in the configuration, this value is used.
     */
    private const DEFAULT_APP_NAME = 'Harmonia';

    /**
     * Constructs a new instance by configuring the session for security, and
     * setting a unique session name.
     *
     * @throws \RuntimeException
     *   If session support is disabled or already active, if setting session
     *   initialization options fails, if setting session cookie parameters
     *   fails, or if setting the session name fails.
     *
     */
    protected function __construct()
    {
        // Ensures session support is enabled and not already active. The
        // configuration won't be effective if the session is already started.
        switch ($this->_session_status()) {
        case \PHP_SESSION_DISABLED:
            throw new \RuntimeException('Session support is disabled.');
        case \PHP_SESSION_ACTIVE:
            throw new \RuntimeException('Session is already active.');
        }

        // Enforces strict session ID validation. Prevents PHP from accepting
        // uninitialized or invalid session IDs from `$_GET`, `$_POST`, and
        // `$_COOKIE`. This mitigates session fixation attacks.
        $this->_ini_set('session.use_strict_mode', '1');
        // Ensures that session IDs are only stored in cookies. This prevents
        // session IDs from being included in URL parameters, reducing exposure
        // to session hijacking.
        $this->_ini_set('session.use_cookies', '1');
        // Disables the use of URL-based session IDs (`session.use_trans_sid`
        // must also be 0). This ensures session IDs are exclusively managed via
        // cookies.
        $this->_ini_set('session.use_only_cookies', '1');
        // Disables PHP's transparent session ID management. Prevents PHP from
        // appending session IDs to URLs, which could expose them in logs.
        $this->_ini_set('session.use_trans_sid', '0');
        // Prevents caching of session data. This ensures that sensitive session
        // information is not stored in the browser's cache.
        $this->_ini_set('session.cache_limiter', 'nocache');

        // Configure the session cookie parameters to enhance security.
        $this->_session_set_cookie_params([
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

        // Set the session name to a unique value for the application.
        $appName = Config::Instance()->OptionOrDefault('AppName', '');
        if ($appName === '') {
            $appName = self::DEFAULT_APP_NAME;
        }
        $this->_session_name("{$appName}_SID");
    }

    #region public -------------------------------------------------------------

    /**
     * Starts a new session or resumes an existing one.
     *
     * If session support is disabled or the session is already started, this
     * method does nothing.
     *
     * @return self
     *   The current instance.
     * @throws \RuntimeException
     *   If starting the session or regenerating the session ID fails.
     */
    public function Start(): self
    {
        if ($this->_session_status() !== \PHP_SESSION_NONE) {
            return $this;
        }
        $this->_session_start();
        $this->_session_regenerate_id();
        return $this;
    }

    /**
     * Saves session data and closes the session.
     *
     * If the session is not started, this method does nothing.
     *
     * @return self
     *   The current instance.
     * @throws \RuntimeException
     *   If writing and closing the session fails.
     */
    public function Close(): self
    {
        if ($this->_session_status() !== \PHP_SESSION_ACTIVE) {
            return $this;
        }
        $this->_session_write_close();
        return $this;
    }

    /**
     * Sets a session variable.
     *
     * If the session is not started, this method does nothing.
     *
     * @param string $key
     *   The name of the session variable.
     * @param mixed $value
     *   The value of the session variable.
     * @return self
     *   The current instance.
     */
    public function Set(string $key, mixed $value): self
    {
        if ($this->_session_status() !== \PHP_SESSION_ACTIVE) {
            return $this;
        }
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * Retrieves a session variable.
     *
     * If the session is not started, this method returns the default value.
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
        if ($this->_session_status() !== \PHP_SESSION_ACTIVE) {
            return $defaultValue;
        }
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
     * If the session is not started, this method does nothing.
     *
     * @param string $key
     *   The name of the session variable.
     * @return self
     *   The current instance.
     */
    public function Remove(string $key): self
    {
        if ($this->_session_status() !== \PHP_SESSION_ACTIVE) {
            return $this;
        }
        unset($_SESSION[$key]);
        return $this;
    }

    /**
     * Clears all session variables.
     *
     * If the session is not started, this method does nothing.
     *
     * @return self
     *   The current instance.
     * @throws \RuntimeException
     *   If clearing session data fails.
     */
    public function Clear(): self
    {
        if ($this->_session_status() !== \PHP_SESSION_ACTIVE) {
            return $this;
        }
        $this->_session_unset();
        return $this;
    }

    /**
     * Destroys the current session.
     *
     * If the session is not started, this method does nothing.
     *
     * @return self
     *   The current instance.
     * @throws \RuntimeException
     *   If HTTP headers have already been sent, if obtaining the session name
     *   fails, if deleting the session cookie fails, if clearing session data
     *   fails, or if destroying the session fails.
     */
    public function Destroy(): self
    {
        if ($this->_session_status() !== \PHP_SESSION_ACTIVE) {
            return $this;
        }
        if (!CookieService::Instance()->DeleteCookie($this->_session_name())) {
            throw new \RuntimeException('Failed to delete session cookie.');
        }
        $this->_session_unset();
        $this->_session_destroy();
        return $this;
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /** @codeCoverageIgnore */
    protected function _ini_set(string $option, mixed $value): void
    {
        if (\ini_set($option, $value) === false) {
            throw new \RuntimeException('Failed to set initialization option.');
        }
    }

    /** @codeCoverageIgnore */
    protected function _session_set_cookie_params(array $lifetime_or_options): void
    {
        if (!\session_set_cookie_params($lifetime_or_options)) {
            throw new \RuntimeException('Failed to set session cookie parameters.');
        }
    }

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
            $previousName = \session_name($name);
            if ($previousName === false) {
                throw new \RuntimeException('Failed to set session name.');
            }
            return $previousName;
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
    protected function _session_regenerate_id(): void
    {
        if (!\session_regenerate_id(true)) {
            throw new \RuntimeException('Failed to regenerate session ID.');
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
