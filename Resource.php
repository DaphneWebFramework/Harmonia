<?php declare(strict_types=1);
/**
 * Resource.php
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

use \Harmonia\Core\CArray;
use \Harmonia\Core\CString;
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;

/**
 * Provides access to application resources such as file system paths and URLs.
 */
class Resource extends Singleton
{
    private const CACHE_KEY_APPRELATIVEPATH = 'appRelativePath';
    private const CACHE_KEY_APPURL = 'appUrl';

    /**
     * Stores the absolute path to the application's root directory.
     *
     * Never use this property directly. Use the `AppPath` method instead.
     *
     * @var ?CPath
     */
    private ?CPath $appPath;

    /**
     * Stores cached values to avoid expensive operations.
     *
     * @var CArray
     */
    protected readonly CArray $cache;

    /**
     * Constructs a new instance.
     *
     * The `Initialize` method must be called before using any method of this
     * class.
     *
     * @see Initialize
     */
    protected function __construct()
    {
        $this->appPath = null;
        $this->cache = new CArray();
    }

    #region public -------------------------------------------------------------

    /**
     * Initializes the resource with the specified application path.
     *
     * The application path refers to the root directory of the application.
     * This method is typically called in a bootstrap file (e.g., bootstrap.php
     * or autoload.php) using `__DIR__` to pass the root directory path.
     *
     * For example:
     * ```php
     * use \Harmonia\Resource;
     *
     * Resource::Instance()->Initialize(__DIR__);
     * ```
     *
     * @param string|\Stringable $appPath
     *   The application path to initialize with.
     * @throws \RuntimeException
     *   If the resource is already initialized or the specified application
     *   path cannot be resolved.
     */
    public function Initialize(string|\Stringable $appPath): void
    {
        if ($this->appPath !== null) {
            throw new \RuntimeException('Resource is already initialized.');
        }
        if (!($appPath instanceof CPath)) {
            $appPath = new CPath($appPath);
        }
        $this->appPath = $appPath->ToAbsolute();
        if ($this->appPath === null) {
            throw new \RuntimeException('Failed to resolve application path.');
        }
    }

    /**
     * Retrieves the application path.
     *
     * @return CPath
     *   The application path.
     * @throws \RuntimeException
     *   If the resource is not initialized.
     */
    public function AppPath(): CPath
    {
        if ($this->appPath === null) {
            throw new \RuntimeException('Resource is not initialized.');
        }
        return $this->appPath;
    }

    /**
     * Retrieves the application's path relative to the server's root directory.
     *
     * The application relative path is always returned with forward slashes
     * which makes it suitable for joining with both file system paths and URLs.
     *
     * For example, if the server's root directory is "C:\xampp\htdocs" and the
     * application path is "C:\xampp\htdocs\MyProjects\CoolApp", this method will
     * return "MyProjects/CoolApp".
     *
     * @return CString
     *   The application relative path.
     * @throws \RuntimeException
     *   If the resource is not initialized, the server path is not available or
     *   cannot be resolved, or the application path is not under the server path.
     */
    public function AppRelativePath(): CString
    {
        if ($this->cache->Has(self::CACHE_KEY_APPRELATIVEPATH)) {
            return $this->cache->Get(self::CACHE_KEY_APPRELATIVEPATH);
        }
        $appPath = $this->AppPath();
        $serverPath = Server::Instance()->Path();
        if ($serverPath === null) {
            throw new \RuntimeException('Server path not available.');
        }
        $serverPath = $serverPath->ToAbsolute();
        if ($serverPath === null) {
            throw new \RuntimeException('Failed to resolve server path.');
        }
        if (!$appPath->StartsWith($serverPath)) {
            throw new \RuntimeException('Application path is not under server path.');
        }
        $result = $appPath
            ->Middle($serverPath->Length())
            ->Replace('\\', '/')
            ->TrimLeft('/');
        $this->cache->Set(self::CACHE_KEY_APPRELATIVEPATH, $result);
        return $result;
    }

    /**
     * Retrieves the application URL.
     *
     * The application URL is formed by joining the server's root URL with the
     * application's relative path. The resulting URL always includes a trailing
     * slash to indicate a directory, ensuring compatibility with browsers and
     * avoiding unnecessary 301 redirects.
     *
     * For example, if the server's root URL is "https://example.com" and the
     * application relative path is "MyProjects/CoolApp", this method will
     * return "https://example.com/MyProjects/CoolApp/".
     *
     * @return CUrl
     *   The application URL.
     * @throws \RuntimeException
     *   If the server URL is not available, the resource is not initialized,
     *   the server path cannot be resolved, or the application path is not
     *   under the server path.
     */
    public function AppUrl(): CUrl
    {
        if ($this->cache->Has(self::CACHE_KEY_APPURL)) {
            return $this->cache->Get(self::CACHE_KEY_APPURL);
        }
        $serverUrl = Server::Instance()->Url();
        if ($serverUrl === null) {
            throw new \RuntimeException('Server URL not available.');
        }
        $result = CUrl::Join($serverUrl, $this->AppRelativePath())
            ->EnsureTrailingSlash();
        $this->cache->Set(self::CACHE_KEY_APPURL, $result);
        return $result;
    }

    #endregion public
}
