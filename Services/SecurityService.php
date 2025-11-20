<?php declare(strict_types=1);
/**
 * SecurityService.php
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
use \Harmonia\Logger;

/**
 * Provides security-related utilities.
 */
class SecurityService extends Singleton
{
    /**
     * The required minimum length of the CSRF secret string.
     */
    private const CSRF_SECRET_MIN_LENGTH = 32;

    /**
     * Represents the secret used for CSRF token HMAC generation/validation.
     *
     * This property is lazy-initialized. Therefore, never use this property
     * directly, use the `csrfSecret` method instead.
     *
     * @var ?string
     */
    private ?string $csrfSecret = null;

    #region public -------------------------------------------------------------

    /**
     * Regular expression pattern that matches bcrypt password hashes.
     */
    public const PASSWORD_HASH_PATTERN = '/^\$2[aby]?\$\d{1,2}\$[.\/A-Za-z0-9]{53}$/';

    /**
     * Recommended minimum password length.
     *
     * A minimum of 8 characters is commonly recommended by standards such as
     * NIST and OWASP. It provides a reasonable baseline against brute-force
     * attacks while remaining usable.
     *
     * This constant exists to avoid hardcoding values and is not enforced by
     * this class.
     */
    public const PASSWORD_MIN_LENGTH = 8;

    /**
     * Recommended maximum password length.
     *
     * This constant reflects the 72-byte input limit of the bcrypt algorithm,
     * which stems from its underlying use of Blowfish (18 DWORDs × 4 bytes).
     *
     * This constant exists to avoid hardcoding values and is not enforced by
     * this class.
     */
    public const PASSWORD_MAX_LENGTH = 72;

    /**
     * Regular expression pattern that matches 64-character hexadecimal tokens.
     *
     * This constant provides a fixed reference for the standard token pattern.
     * It is intended for use in validation rules and in tests, where calling
     * `TokenPattern()` is not practical.
     */
    public const TOKEN_DEFAULT_PATTERN = '/^[0-9a-fA-F]{64}$/';

    /**
     * Hashes a password using a secure hashing algorithm.
     *
     * @param string $password
     *   The plaintext password.
     * @return string
     *   The hashed password.
     */
    public function HashPassword(string $password): string
    {
        return \password_hash($password, \PASSWORD_DEFAULT);
    }

    /**
     * Verifies a plaintext password against a hashed password.
     *
     * @param string $password
     *   The plaintext password.
     * @param string $hash
     *   The hashed password for comparison.
     * @return bool
     *   Returns `true` if the password matches the hash, otherwise `false`.
     */
    public function VerifyPassword(string $password, string $hash): bool
    {
        return \password_verify($password, $hash);
    }

    /**
     * Generates a cryptographically secure random token of a given byte length.
     *
     * @param int $byteLength
     *   (Optional) The number of bytes to generate. Defaults to 32.
     * @return string
     *   A hexadecimal string representing the random bytes. Since each byte is
     *   encoded as two hexadecimal characters, the resulting string length is
     *   exactly twice the specified byte length.
     */
    public function GenerateToken(int $byteLength = 32): string
    {
        if ($byteLength < 1) {
            throw new \InvalidArgumentException('Byte length must be at least 1.');
        }
        return \bin2hex(\random_bytes($byteLength));
    }

    /**
     * Returns a regular expression pattern for validating tokens of a given
     * byte length.
     *
     * @param int $byteLength
     *   (Optional) The number of bytes to validate. Defaults to 32.
     * @return string
     *   A regular expression that matches hexadecimal strings of the appropriate
     *   length. Because each byte corresponds to two hexadecimal characters,
     *   the pattern enforces a string length of twice the specified byte length.
     */
    public function TokenPattern(int $byteLength = 32): string
    {
        if ($byteLength < 1) {
            throw new \InvalidArgumentException('Byte length must be at least 1.');
        }
        $hexLength = $byteLength * 2;
        return "/^[0-9a-fA-F]{{$hexLength}}$/";
    }

    /**
     * Creates a token and its corresponding cookie value.
     *
     * Returns a token (to be included in forms or request headers) and a
     * matching value for storing in a cookie. Together, these values can
     * later be verified to help mitigate cross-site request forgery (CSRF)
     * attacks by ensuring the request originated from the same client.
     *
     * @return array{0: string, 1: string}
     *   A two-element array containing the generated token and its
     *   corresponding cookie value.
     *
     * @see VerifyCsrfPair
     */
    public function GenerateCsrfPair(): array
    {
        $token = $this->GenerateToken();
        $cookieValue = \hash_hmac('sha256', $token, $this->csrfSecret());
        return [$token, $cookieValue];
    }

    /**
     * Verifies a token against its corresponding cookie value.
     *
     * Compares the provided token with the value stored in the cookie. If
     * they match, the request is considered authentic. This check is used
     * to mitigate cross-site request forgery (CSRF) attacks by validating
     * that the request was issued by the same client that received the token.
     *
     * @param string $token
     *   The token received from the client (e.g., from a form field or
     *   request header).
     * @param string $cookieValue
     *   The value retrieved from the cookie.
     * @return bool
     *   Returns `true` if the token matches the cookie value, otherwise `false`.
     *
     * @see GenerateCsrfPair
     */
    public function VerifyCsrfPair(string $token, string $cookieValue): bool
    {
        $expected = \hash_hmac('sha256', $token, $this->csrfSecret());
        return \hash_equals($expected, $cookieValue);
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /**
     * Retrieves the CSRF secret used for HMAC-based token generation/validation.
     *
     * The secret is read lazily from the configuration on first access and
     * then cached for subsequent calls. It must be a cryptographically
     * secure random value of at least `CSRF_SECRET_MIN_LENGTH` characters.
     *
     * If the value is not a string or is empty, an error is logged and the
     * secret is set to an empty string. If the value is shorter than the
     * required minimum length, a warning is logged but the value is still
     * accepted as-is. In these cases, CSRF protection may be effectively
     * disabled, since tokens can be forged without a proper secret.
     *
     * @return string
     *   The CSRF secret, or an empty string if not set or set to a non-string
     *   value.
     */
    protected function csrfSecret(): string
    {
        if ($this->csrfSecret !== null) {
            return $this->csrfSecret;
        }
        $value = Config::Instance()->Option('CsrfSecret');
        if (!\is_string($value)) {
            Logger::Instance()->Error('CSRF secret must be a string.');
            $this->csrfSecret = '';
        } else {
            if ($value === '') {
                Logger::Instance()->Error('CSRF secret must not be empty.');
            } elseif (\strlen($value) < self::CSRF_SECRET_MIN_LENGTH) {
                Logger::Instance()->Warning('CSRF secret must be at least '
                    . self::CSRF_SECRET_MIN_LENGTH . ' characters.');
            }
            $this->csrfSecret = $value;
        }
        return $this->csrfSecret;
    }

    #endregion protected
}
