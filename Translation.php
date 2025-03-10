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
 * the "Language" configuration setting.
 *
 * Subclasses must define their own translation sources by overriding
 * `filePaths()`.
 *
 * The JSON files must contain a mapping of translation IDs to translation units,
 * where each translation unit consists of language codes mapped to translation
 * text. The structure follows this format:
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
 *   }
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
     * @param string $translationId
     *   The identifier of the translation.
     * @param mixed ...$args
     *   (Optional) Arguments for string formatting within the translation.
     * @return string
     *   The translation in the current language.
     * @throws \RuntimeException
     *   When the translation ID or language is not found.
     */
    public function Get(string $translationId, mixed ...$args): string
    {
        $translations = $this->translations();
        if (!$translations->Has($translationId)) {
            throw new \RuntimeException("Translation ID '$translationId' not found.");
        }
        $language = $this->language();
        $unit = $translations->Get($translationId);
        if (!$unit->Has($language)) {
            throw new \RuntimeException(
                "Language '$language' not found for translation ID '$translationId'.");
        }
        $translation = $unit->Get($language);
        if (!empty($args)) {
            $translation = \vsprintf($translation, $args);
        }
        return $translation;
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
     *   A `CArray` where each key maps to another `CArray` of localized
     *   translation units.
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
            $loaded = $this->loadTranslationsFromFile($filePath);
            $translations->ApplyInPlace('\array_replace_recursive', $loaded->ToArray());
        }
        return $this->translations = $translations;
    }

    /**
     * Loads translations from a JSON file.
     *
     * @param CPath $filePath
     *   The path to the JSON file.
     * @return CArray
     *   A `CArray` containing the translations.
     * @throws \RuntimeException
     *   If the file cannot be read or has an invalid structure.
     */
    protected function loadTranslationsFromFile(CPath $filePath): CArray
    {
        $file = $this->openFile($filePath);
        if ($file === null) {
            throw new \RuntimeException('Failed to open translation file.');
        }
        $contents = $file->Read();
        $file->Close();
        if ($contents === null) {
            throw new \RuntimeException('Could not read translation file.');
        }
        $root = \json_decode($contents, true);
        if ($root === null) {
            throw new \RuntimeException('Translation file could not be decoded.');
        }
        if (!\is_array($root)) {
            throw new \RuntimeException(
                'Translation file must have an object as its root structure.');
        }
        foreach ($root as $translationId => $unit) {
            if (!\is_string($translationId)) {
                throw new \RuntimeException('Translation ID must be a string.');
            }
            if ($translationId === '') {
                throw new \RuntimeException('Translation ID cannot be empty.');
            }
            if (!\is_array($unit)) {
                throw new \RuntimeException(
                    'Each translation ID must map to an object of language-text pairs.');
            }
            foreach ($unit as $language => $translation) {
                if (!\is_string($language)) {
                    throw new \RuntimeException('Language code must be a string.');
                }
                if ($language === '') {
                    throw new \RuntimeException('Language code cannot be empty.');
                }
                if (!\is_string($translation)) {
                    throw new \RuntimeException('Translation text must be a string.');
                }
            }
            $root[$translationId] = new CArray($unit);
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
            throw new \RuntimeException('Language setting is not a string.');
        }
        return $this->language = $language;
    }

    /** @codeCoverageIgnore */
    protected function openFile(CPath $filePath): ?CFile
    {
        return CFile::Open($filePath, CFile::MODE_READ);
    }

    #endregion protected
}
