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
use \Harmonia\Core\CPath;
use \Harmonia\Core\CUrl;

/**
 * Provides access to application resources such as file system paths and URLs.
 */
class Resource extends Singleton
{
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
    private readonly CArray $cache;

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
     * The application path refers to the root directory of your project.
     * This method is typically called in a file such as `autoload.php`,
     * using `__DIR__` to pass that path.
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
        if (!$appPath instanceof CPath) {
            $appPath = new CPath($appPath);
        }
        try {
            $appPath->ApplyInPlace('\realpath');
        } catch (\UnexpectedValueException $e) {
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
     * The application relative path is always returned with forward slashes,
     * making it suitable for use in both file system paths and URLs.
     *
     * **Example**: When the app is physically inside the server path
     *   - Server root: `C:\xampp\htdocs`
     *   - Application path: `C:\xampp\htdocs\MyProjects\MyApp`
     *   - Returns: `MyProjects/MyApp`
     *
     * This method also supports cases where the application is symlinked inside
     * the server directory. If the application path is not physically located
     * under the server root, but a symbolic link inside the server directory
     * points to the application path, this method will correctly resolve the
     * link and compute the relative path accordingly.
     *
     * **Example**: When the app is symlinked inside the server path
     *   - Server root: `/var/www/html`
     *   - Application path: `/home/user/projects/myapp`
     *   - Symlink: `/var/www/html/myapp" → "/home/user/projects/myapp`
     *   - Returns: `myapp`
     *
     * @return CPath
     *   The application's relative path.
     * @throws \RuntimeException
     *   If the resource is not initialized, the server path is not available
     *   or cannot be resolved, or the application path is neither under the
     *   server path nor accessible via a valid symlink inside the server
     *   directory.
     */
    public function AppRelativePath(): CPath
    {
        if ($this->cache->Has(__FUNCTION__)) {
            return $this->cache->Get(__FUNCTION__);
        }
        $appPath = $this->AppPath();
        $serverPath = Server::Instance()->Path();
        if ($serverPath === null) {
            throw new \RuntimeException('Server path not available.');
        }
        try {
            $serverPath->ApplyInPlace('\realpath');
        } catch (\UnexpectedValueException $e) {
            throw new \RuntimeException('Failed to resolve server path.');
        }
        if (!$appPath->StartsWith($serverPath)) {
            // Linux/macOS only: If the application path is not under the server
            // path, check if the server directory contains a symbolic link that
            // resolves to the application path.
            $serverDirectoryContainsLinkToAppPath = false;
            if (\PHP_OS_FAMILY !== 'Windows') {
                $linkPath = CPath::Join($serverPath, $appPath->Apply('\basename'));
                if ($linkPath->IsLink()) {
                    $targetPath = $linkPath->ReadLink();
                    if ($targetPath !== null && $targetPath->Equals($appPath)) {
                        $appPath = $linkPath;
                        $serverDirectoryContainsLinkToAppPath = true;
                    }
                }
            }
            if (!$serverDirectoryContainsLinkToAppPath) {
                throw new \RuntimeException('Application path is not under server path.');
            }
        }
        $result = $appPath
            ->Middle($serverPath->Length())
            ->Replace('\\', '/')
            ->TrimLeft('/');
        $this->cache->Set(__FUNCTION__, $result);
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
     * **Example**:
     *   - Server URL: `https://example.com`
     *   - Application relative path: `MyProjects/MyApp`
     *   - Returns: `https://example.com/MyProjects/MyApp/`
     *
     * @return CUrl
     *   The application URL.
     * @throws \RuntimeException
     *   If the server URL is not available, the resource is not initialized,
     *   the server path cannot be resolved, or the application path is neither
     *   under the server path nor accessible via a valid symlink inside the
     *   server directory.
     */
    public function AppUrl(): CUrl
    {
        if ($this->cache->Has(__FUNCTION__)) {
            return $this->cache->Get(__FUNCTION__);
        }
        $serverUrl = Server::Instance()->Url();
        if ($serverUrl === null) {
            throw new \RuntimeException('Server URL not available.');
        }
        $result = CUrl::Join($serverUrl, $this->AppRelativePath())
            ->EnsureTrailingSlash();
        $this->cache->Set(__FUNCTION__, $result);
        return $result;
    }

    /**
     * Returns the absolute path to a subdirectory under the application root.
     *
     * @param string $subdirectory
     *   The name of the subdirectory.
     * @return CPath
     *   The absolute path to the subdirectory.
     */
    public function AppSubdirectoryPath($subdirectory): CPath
    {
        $cacheKey = __FUNCTION__ . "($subdirectory)";
        if ($this->cache->Has($cacheKey)) {
            return $this->cache->Get($cacheKey);
        }
        $result = CPath::Join($this->AppPath(), $subdirectory);
        $this->cache->Set($cacheKey, $result);
        return $result;
    }

    /**
     * Returns the absolute URL to a subdirectory under the application root.
     *
     * @param string $subdirectory
     *   The name of the subdirectory.
     * @return CUrl
     *   The URL to the subdirectory.
     */
    public function AppSubdirectoryUrl(string $subdirectory): CUrl
    {
        $cacheKey = __FUNCTION__ . "($subdirectory)";
        if ($this->cache->Has($cacheKey)) {
            return $this->cache->Get($cacheKey);
        }
        $url = CUrl::Join($this->AppUrl(), $subdirectory);
        $this->cache->Set($cacheKey, $url);
        return $url;
    }

    #endregion public
}
