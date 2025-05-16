<?php declare(strict_types=1);
/**
 * FileRule.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem\Rules;

use \Harmonia\Systems\ValidationSystem\Messages;

/**
 * Validates whether a given field contains an uploaded file.
 */
class FileRule extends Rule
{
    /**
     * Validates that the field contains an uploaded file.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   Unused in this rule.
     * @throws \RuntimeException
     *   If the value is not a valid uploaded file or an upload error occurs.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if ($this->nativeFunctions->IsUploadedFile($value)) {
            return;
        }
        $this->checkUploadError($value);
        throw new \RuntimeException(Messages::Instance()->Get(
            'field_must_be_a_file',
            $field
        ));
    }

    private function checkUploadError(mixed $value): void
    {
        if (!\is_array($value) || !\array_key_exists('error', $value)
         || !\is_int($value['error']) || $value['error'] === \UPLOAD_ERR_OK)
        {
            return;
        }
        throw new \RuntimeException(match ($value['error']) {
            \UPLOAD_ERR_INI_SIZE =>
                'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            \UPLOAD_ERR_FORM_SIZE =>
                'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            \UPLOAD_ERR_PARTIAL =>
                'The uploaded file was only partially uploaded.',
            \UPLOAD_ERR_NO_FILE =>
                'No file was uploaded.',
            \UPLOAD_ERR_NO_TMP_DIR =>
                'Missing a temporary folder.',
            \UPLOAD_ERR_CANT_WRITE =>
                'Failed to write file to disk.',
            \UPLOAD_ERR_EXTENSION =>
                'A PHP extension stopped the file upload.',
            default =>
                "Unknown upload error: {$value['error']}",
        });
    }
}
