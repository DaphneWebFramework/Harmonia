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

use \Harmonia\Services\Security\CsrfToken;

/**
 * Provides security-related utilities.
 */
class SecurityService extends Singleton
{
    #region public -------------------------------------------------------------

    /**
     * Regular expression pattern that matches 64-character lowercase
     * hexadecimal tokens.
     */
    public const TOKEN_PATTERN = '/^[a-f0-9]{64}$/';

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
     * Generates a cryptographically secure random token.
     *
     * @return string
     *   A 64-character hexadecimal token.
     */
    public function GenerateToken(): string
    {
        return \bin2hex(\random_bytes(32));
    }

    /**
     * Generates a CSRF token and its hashed cookie value.
     *
     * @return CsrfToken
     *   A `CsrfToken` instance containing the token and its obfuscated hash.
     */
    public function GenerateCsrfToken(): CsrfToken
    {
        $token = $this->GenerateToken();
        $cookieValue = $this->obfuscate(
            $this->HashPassword($token)
        );
        return new CsrfToken($token, $cookieValue);
    }

    /**
     * Verifies whether a CSRF token matches its expected hash.
     *
     * @param CsrfToken $csrfToken
     *   The CSRF token instance to verify.
     * @return bool
     *   Returns `true` if the token is valid, otherwise `false`.
     */
    public function VerifyCsrfToken(CsrfToken $csrfToken): bool
    {
        return $this->VerifyPassword(
            $csrfToken->Token(),
            $this->deobfuscate(
                $csrfToken->CookieValue()
            )
        );
    }

    #endregion public

    #region private ------------------------------------------------------------

    /**
     * Obfuscates a string to prevent direct comparison attacks.
     *
     * @param string $data
     *   The string to obfuscate.
     * @return string
     *   The obfuscated string.
     */
    private function obfuscate(string $data): string
    {
        return \bin2hex(\strrev($data));
    }

    /**
     * Reverses the obfuscation process on a string.
     *
     * @param string $data
     *   The obfuscated string.
     * @return string
     *   The original string if decoding succeeds, otherwise an empty string.
     */
    private function deobfuscate(string $data): string
    {
        if (!\ctype_xdigit($data) || (\strlen($data) % 2 !== 0)) {
            return '';
        }
        $decoded = \hex2bin($data);
        if ($decoded === false) {
            return '';
        }
        return \strrev($decoded);
    }

    #endregion private
}
