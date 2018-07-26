<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use VovanVE\Yii2I18nJsonExport\helpers\DataUtils;
use VovanVE\Yii2I18nJsonExport\MergeConflictException;
use VovanVE\Yii2I18nJsonExport\SourceDataException;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Translations storage driver with files in language directory.
 *
 * Translation for message `\Yii::t('category/subcategory', 'Test message')`
 * for `ru-RU` language will be stored in `ru-RU/*.json` file
 * and will contain:
 *
 * ```json
 * {
 *     "category/subcategory": {
 *         "Test message": "Тестовое сообщение"
 *     }
 * }
 * ```
 *
 * Exact file name depends on its nature. Files in same directory can safely share
 * some messages.
 *
 * On export all files will be read and merged. On import each file will be updated separately.
 */
class FlatDirectoryDriver extends BaseFlatCategoryDriver
{
    /**
     * @inheritdoc
     * @throws SourceDataException
     * @throws MergeConflictException
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
            'only' => ["/*/*.{$this->extension}"],
            'recursive' => false,
        ]);
        foreach ($files as $file) {
            $language = self::parsePathLanguage($file, $base_path, $this->extension);
            if (!$language) {
                continue;
            }

            $messages = $this->loadLanguageFromFile($file);

            if (!isset($data[$language])) {
                $data[$language] = $messages;
                continue;
            }

            $res_language = &$data[$language];
            try {
                DataUtils::mergeSourceLanguage($res_language, $messages);
            } catch (MergeConflictException $e) {
                throw new MergeConflictException(
                    $language,
                    $e->category,
                    $e->message,
                    $e->translations
                );
            }
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
            $language_path = $real_path . '/' . $language;
            $files = FileHelper::findFiles($language_path, [
                'only' => ["*.{$this->extension}"],
                'recursive' => false,
            ]);
            foreach ($files as $file) {
                $old = $this->loadLanguageFromFile($file);
                $new = $onlyExisting
                    ? $this->updateTranslationsArray($old, $categories)
                    : $this->fillTranslationsArray($categories, $old);
                if ($new !== $old) {
                    $this->saveLanguageToFile($file . $extraExtension, $new);
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
        $path = strtr($file, '\\', '/');
        $dot_ext = '.' . $extension;

        if (!StringHelper::startsWith($path, $basePath)) {
            throw new \InvalidArgumentException('File outside of base path');
        }

        // /foo/bar/baz/file.json
        // /foo/bar/
        // =>
        //          bar/file.json
        $sub_path = StringHelper::byteSubstr($path, StringHelper::byteLength($basePath));

        // bar/file.json
        //         -----
        if (StringHelper::endsWith($sub_path, $dot_ext)) {
            // bar/file.json
            //         ----- DEL
            // =>
            // bar/file
            $sub_path = StringHelper::byteSubstr($sub_path, 0, -StringHelper::byteLength($dot_ext));

            // Edge cases:
            // bar/.json => bar/
            // .json     => ''
            if ('' === $sub_path || StringHelper::endsWith($sub_path, '/')) {
                return null;
            }
        }

        // "ru-RU/foo" => "ru-RU"
        return explode('/', $sub_path)[0];
    }
}
