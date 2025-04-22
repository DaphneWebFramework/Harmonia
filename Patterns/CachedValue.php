<?php declare(strict_types=1);
/**
 * CachedValue.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Patterns;

/**
 * Provides lazy caching of a nullable value.
 *
 * The first time the value is accessed via `Get`, it is resolved by evaluating
 * the provided closure. The result is then cached, including `null` values, and
 * reused for subsequent calls.
 */
class CachedValue
{
    private mixed $value = null;
    private bool $isCached = false;

    /**
     * Returns the cached value if available; otherwise, resolves and caches it.
     *
     * @param callable $resolver
     *   A callable that provides the value if it has not been cached yet.
     * @return mixed
     *   The cached value, or the result of the resolver.
     */
    public function Get(callable $resolver): mixed
    {
        if (!$this->isCached) {
            $this->value = $resolver();
            $this->isCached = true;
        }
        return $this->value;
    }

    /**
     * Manually sets the cached value and marks it as cached.
     *
     * Useful for test injection or manually assigning a known value.
     *
     * @param mixed $value
     *   The value to cache.
     */
    public function Set(mixed $value): void
    {
        $this->value = $value;
        $this->isCached = true;
    }

    /**
     * Checks whether the value has already been resolved and cached.
     *
     * @return bool
     *   Returns `true` if the value has been cached; `false` otherwise.
     */
    public function IsCached(): bool
    {
        return $this->isCached;
    }

    /**
     * Clears the cached value and resets the cached state.
     *
     * Useful for invalidation or forcing the value to be re-resolved.
     */
    public function Reset(): void
    {
        $this->value = null;
        $this->isCached = false;
    }
}
