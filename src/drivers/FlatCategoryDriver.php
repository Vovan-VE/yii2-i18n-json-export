<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use VovanVE\Yii2I18nJsonExport\SourceDataException;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Translations storage driver with single file per language.
 *
 * Translation for message `\Yii::t('category/subcategory', 'Test message')`
 * for `ru-RU` language will be stored in `ru-RU.json` file
 * and will contain:
 *
 * ```json
 * {
 *     "category/subcategory": {
 *         "Test message": "Тестовое сообщение"
 *     }
 * }
 * ```
 */
class FlatCategoryDriver extends BaseFlatCategoryDriver
{
    /**
     * @inheritdoc
     * @throws SourceDataException
     */
    public function loadAllTranslations()
    {
        $data = [];

        $abs_path = \Yii::getAlias($this->path);
        $real_path = realpath($abs_path);
        if (false === $real_path) {
            throw new \RuntimeException("Cannot check path: $abs_path");
        }

        $base_path = strtr($real_path, '\\', '/');
        $base_path = rtrim($base_path, '/') . '/';

        $files = FileHelper::findFiles($real_path, [
            'only' => ["*.{$this->extension}"],
            'recursive' => false,
        ]);
        foreach ($files as $file) {
            $language = self::parsePathLanguage($file, $base_path, $this->extension);
            if (!$language) {
                continue;
            }

            $data[$language] = $this->loadLanguageFromFile($file);
        }

        return $data;
    }

    /**
     * @inheritdoc
     * @throws SourceDataException
     */
    public function saveAllTranslations($data, $onlyExisting = true, $extraExtension = '')
    {
        $abs_path = \Yii::getAlias($this->path);
        $real_path = realpath($abs_path);
        if (false === $real_path) {
            throw new \RuntimeException("Cannot check path: $abs_path");
        }

        foreach ($data as $language => $categories) {
            $file = $real_path . '/' . $language . '.' . $this->extension;

            if (is_file($file)) {
                $old = $this->loadLanguageFromFile($file);
                $new = $onlyExisting
                    ? $this->updateTranslationsArray($old, $categories)
                    : $this->fillTranslationsArray($categories, $old);
                if ($new !== $old) {
                    $this->saveLanguageToFile($file . $extraExtension, $new);
                }
            } else {
                if (!$onlyExisting) {
                    $this->saveLanguageToFile($file . $extraExtension, $categories);
                }
            }
        }
    }

    /**
     * Parse language from file path
     * @param string $file Filename to parse
     * @param string $basePath Base path with '/' directory separator and with '/' in the end
     * @param string $extension Base name extension to strip
     * @return string
     */
    private static function parsePathLanguage($file, $basePath, $extension)
    {
        // yes, you know, `basename()` sucks

        $path = strtr($file, '\\', '/');
        $dot_ext = '.' . $extension;

        if (!StringHelper::startsWith($path, $basePath)) {
            throw new \InvalidArgumentException('File outside of base path');
        }

        // /foo/bar/file.json
        // /foo/bar/
        // =>
        //          file.json
        $basename = StringHelper::byteSubstr($path, StringHelper::byteLength($basePath));

        if (false !== strpos($basename, '/')) {
            throw new \InvalidArgumentException('File from nested subdirectories');
        }

        // file.json
        //     -----
        if (StringHelper::endsWith($basename, $dot_ext)) {
            // file.json
            //     ----- DEL
            // =>
            // file
            $basename = StringHelper::byteSubstr($basename, 0, -StringHelper::byteLength($dot_ext));

            // Edge cases:
            // '.json' => ''
            if ('' === $basename) {
                return null;
            }
        }

        return $basename;
    }
}
