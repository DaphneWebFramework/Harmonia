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
    /**
     * The cache key for the application-relative path.
     */
    private const CACHE_KEY_APPRELATIVEPATH = 'appRelativePath';

    /**
     * The cache key for the application URL.
     */
    private const CACHE_KEY_APPURL = 'appUrl';

    /**
     * Stores the absolute path to the application's root directory.
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
     * Stores the server instance used to resolve server-related paths and URLs.
     *
     * @var Server
     */
    protected readonly Server $server;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * The `Initialize` method must be called before using any method of this
     * class.
     *
     * @see Initialize
     */
    public function __construct()
    {
        $this->appPath = null;
        $this->cache = new CArray();
        $this->server = Server::Instance();
    }

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
     * use \Harmonia\Core\CPath;
     *
     * Resource::Instance()->Initialize(new CPath(__DIR__));
     * ```
     *
     * @param CPath $appPath
     *   The application path to initialize with.
     * @throws \RuntimeException
     *   If the application path cannot be resolved.
     */
    public function Initialize(CPath $appPath): void
    {
        $appPath = $appPath->ToAbsolute();
        if ($appPath === null) {
            throw new \RuntimeException('Failed to resolve application path.');
        }
        $this->appPath = $appPath;
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
     *   If the resource is not initialized, the server path cannot be resolved,
     *   or the application path is not under the server path.
     */
    public function AppRelativePath(): CString
    {
        if ($this->cache->Has(self::CACHE_KEY_APPRELATIVEPATH)) {
            return $this->cache->Get(self::CACHE_KEY_APPRELATIVEPATH);
        }
        $appPath = $this->AppPath();
        $serverPath = $this->server->Path()->ToAbsolute();
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
     *   If the resource is not initialized, the server path cannot be resolved,
     *   or the application path is not under the server path.
     */
    public function AppUrl(): CUrl
    {
        if ($this->cache->Has(self::CACHE_KEY_APPURL)) {
            return $this->cache->Get(self::CACHE_KEY_APPURL);
        }
        $result = CUrl::Join($this->server->Url(), $this->AppRelativePath())
            ->EnsureTrailingSlash();
        $this->cache->Set(self::CACHE_KEY_APPURL, $result);
        return $result;
    }

    #endregion public
}
