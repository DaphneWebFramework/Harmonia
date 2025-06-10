<?php declare(strict_types=1);
/**
 * Translation.php
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
use \Harmonia\Core\CArray;
use \Harmonia\Core\CFile;
use \Harmonia\Core\CPath;

/**
 * Abstract base class for managing translations across the application.
 *
 * This class loads and merges translations from one or more JSON files,
 * supporting multiple languages. The current language is determined by
 * the "Language" configuration option.
 *
 * Subclasses must define their own translation sources by overriding
 * `filePaths()`.
 *
 * The JSON files must contain a mapping of translation keys to translation
 * units. Each translation unit can be either:
 *
 * - An object mapping language codes (e.g., "en", "tr") to localized strings.
 * - A string referencing another translation key (an alias).
 *
 * Aliases may point to other aliases, forming recursive chains that are
 * resolved automatically. Cycles in alias chains are detected and rejected.
 *
 * **Example JSON structure:**
 * ```json
 * {
 *   "welcome_message": {
 *     "en": "Welcome to our application!",
 *     "tr": "Uygulamamıza hoş geldiniz!",
 *     "se": "Välkommen till vår applikation!"
 *   },
 *   "logout_confirmation": {
 *     "en": "Are you sure you want to log out?",
 *     "tr": "Çıkış yapmak istediğinizden emin misiniz?",
 *     "se": "Är du säker på att du vill logga ut?"
 *   },
 *   "members_welcome_message": "welcome_message"
 * }
 * ```
 *
 * > Exceptions thrown within this class use fixed messages and are not subject
 * to localization or customization through the `Translation` class itself.
 */
abstract class Translation extends Singleton
{
    /**
     * Holds the translations loaded from the JSON file.
     *
     * This property is lazy-initialized. Therefore, never access it directly;
     * use the `translations()` method instead.
     *
     * @var ?CArray
     */
    private ?CArray $translations = null;

    /**
     * Holds the current language as obtained from the configuration.
     *
     * This property is lazy-initialized. Therefore, never access it directly;
     * use the `language()` method instead.
     *
     * @var ?string
     */
    private ?string $language = null;

    #region public -------------------------------------------------------------

    /**
     * Retrieves a translation in the current application language.
     *
     * Additional arguments can be passed for string formatting within the
     * translation. For example: `Get('field_must_be_numeric', 'price')`
     *
     * @param string $key
     *   The translation key.
     * @param mixed ...$args
     *   (Optional) Arguments for string formatting within the translation.
     * @return string
     *   The translation text in the current language.
     * @throws \RuntimeException
     *   When the translation key or language is not found, or alias cycle
     *   detected.
     */
    public function Get(string $key, mixed ...$args): string
    {
        return $this->resolve($key, $args, []);
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /**
     * Returns the list of translation file paths.
     *
     * Subclasses must override this method to define their own sources.
     *
     * #### Example: Direct Subclass Implementation
     *
     * A subclass that directly extends `Harmonia\Translation` and specifies
     * a single JSON file as the translation source.
     *
     * ```php
     * namespace MyLibrary;
     *
     * use \Harmonia\Core\CPath;
     *
     * class Translation extends \Harmonia\Translation
     * {
     *     protected function filePaths(): array
     *     {
     *         return [CPath::Join(__DIR__, 'translations.json')];
     *     }
     * }
     * ```
     *
     * #### Example: Extending Another Translation Class
     *
     * A subclass that extends an existing translation class while adding
     * additional translation sources. The parent's translation files
     * are merged with new ones using `array_merge()`.
     *
     * ```php
     * namespace App;
     *
     * use \Harmonia\Core\CPath;
     *
     * class Translation extends \MyLibrary\Translation
     * {
     *     protected function filePaths(): array
     *     {
     *         return \array_merge(parent::filePaths(), [
     *             CPath::Join(__DIR__, 'translations.json')
     *         ]);
     *     }
     * }
     * ```
     *
     * @return array<CPath>
     *   An array of paths to translation JSON files.
     */
    abstract protected function filePaths(): array;

    /**
     * Retrieves the array of translations.
     *
     * Loads the translations from one or more JSON files the first time this
     * method is called. Subsequent calls return the already loaded translations.
     *
     * @return CArray
     *   A `CArray` where each translation key maps to either a nested `CArray`
     *   of localized language-text pairs, or a string alias referencing another
     *   translation key.
     * @throws \RuntimeException
     *   If there is an error reading the file or decoding its JSON content.
     */
    protected function translations(): CArray
    {
        if ($this->translations !== null) {
            return $this->translations;
        }
        $translations = new CArray();
        foreach ($this->filePaths() as $filePath) {
            $loaded = $this->loadFile($filePath);
            $translations->ApplyInPlace('\array_replace_recursive', $loaded->ToArray());
        }
        return $this->translations = $translations;
    }

    /**
     * Loads and parses the file, returning validated translation units or
     * aliases.
     *
     * @param CPath $filePath
     *   The path to the translation file.
     * @return CArray
     *   A `CArray` where each translation key maps to either a nested `CArray`
     *   of localized language-text pairs, or a string alias referencing another
     *   translation key.
     * @throws \RuntimeException
     *   If the file cannot be opened, read, decoded, or validated.
     */
    protected function loadFile(CPath $filePath): CArray
    {
        $file = $this->openFile($filePath);
        if ($file === null) {
            throw new \RuntimeException('Translation file could not be opened.');
        }
        $contents = $file->Read();
        $file->Close();
        if ($contents === null) {
            throw new \RuntimeException('Translation file could not be read.');
        }
        $root = \json_decode($contents, true);
        if ($root === null) {
            throw new \RuntimeException('Translation file contains invalid JSON.');
        }
        if (!\is_array($root)) {
            throw new \RuntimeException(
                'Translation file must contain an object at the root.');
        }
        foreach ($root as $key => $unit) {
            if (!\is_string($key)) {
                throw new \RuntimeException('Translation key must be a string.');
            }
            if ($key === '') {
                throw new \RuntimeException('Translation key cannot be empty.');
            }
            if (\is_array($unit)) {
                foreach ($unit as $language => $text) {
                    if (!\is_string($language)) {
                        throw new \RuntimeException('Language code must be a string.');
                    }
                    if ($language === '') {
                        throw new \RuntimeException('Language code cannot be empty.');
                    }
                    if (!\is_string($text)) {
                        throw new \RuntimeException('Translation text must be a string.');
                    }
                }
                $root[$key] = new CArray($unit);
            } elseif (\is_string($unit)) {
                $root[$key] = $unit; // Alias
            } else {
                throw new \RuntimeException(
                    'Translation unit must be an object of language-text pairs or an alias string.');
            }
        }
        return new CArray($root);
    }

    /**
     * Retrieves the current language from the configuration.
     *
     * @return string
     *   The current language.
     * @throws \RuntimeException
     *   If the language is not set in the configuration or is not a string.
     */
    protected function language(): string
    {
        if ($this->language !== null) {
            return $this->language;
        }
        $language = Config::Instance()->Option('Language');
        if ($language === null) {
            throw new \RuntimeException('Language not set in configuration.');
        }
        if (!\is_string($language)) {
            throw new \RuntimeException('Language code must be a string.');
        }
        return $this->language = $language;
    }

    /** @codeCoverageIgnore */
    protected function openFile(CPath $filePath): ?CFile
    {
        return CFile::Open($filePath, CFile::MODE_READ);
    }

    #endregion protected

    #region private ------------------------------------------------------------

    /**
     * Recursively resolves translation keys and performs alias handling.
     *
     * @param string $key
     *   The translation key.
     * @param array $args
     *   Arguments for string formatting within the translation.
     * @param array $visited
     *   Previously visited translation keys (for alias cycle detection).
     * @return string
     *   The translation text in the current language.
     * @throws \RuntimeException
     *   When the translation key or language is not found, or alias cycle
     *   detected.
     */
    private function resolve(string $key, array $args, array $visited): string
    {
        if (\in_array($key, $visited, true)) {
            throw new \RuntimeException(
                "Alias cycle detected with translation '$key'.");
        }
        $translations = $this->translations();
        if (!$translations->Has($key)) {
            throw new \RuntimeException("Translation '$key' not found.");
        }
        $unit = $translations->Get($key);
        if (\is_string($unit)) { // Alias
            $visited[] = $key;
            return $this->resolve($unit, $args, $visited);
        }
        $language = $this->language();
        if (!$unit->Has($language)) {
            throw new \RuntimeException(
                "Language '$language' not found for translation '$key'.");
        }
        $text = $unit->Get($language);
        if (!empty($args)) {
            $text = \vsprintf($text, $args);
        }
        return $text;
    }

    #endregion private
}
