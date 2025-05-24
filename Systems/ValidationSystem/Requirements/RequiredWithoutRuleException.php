<?php declare(strict_types=1);
/**
 * RequiredWithoutRuleException.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem\Requirements;

/**
 * Raised when a field fails a `requiredWithout` rule check.
 */
class RequiredWithoutRuleException extends \RuntimeException
{
}
