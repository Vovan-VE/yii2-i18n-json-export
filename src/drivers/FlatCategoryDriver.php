<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use yii\base\Component;
use yii\base\InvalidConfigException;
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
class FlatCategoryDriver extends Component implements DriverInterface
{
    use CategoryUtilsTrait;
    use JsonDriverTrait;

    /** @var string Path to messages root for all languages */
    public $path;

    /** @var string File extension without dot like 'json' */
    public $extension = 'json';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (null === $this->path) {
            throw new InvalidConfigException('Option "path" is required');
        }
    }

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

            if ($onlyExisting) {
                if (!is_file($file)) {
                    continue;
                }

                $existing = $this->loadLanguageFromFile($file);
                $new = $this->updateTranslationsArray($existing, $categories);
                if ($new !== $existing) {
                    $this->saveLanguageToFile($file . $extraExtension, $new);
                }
            } else {
                $this->saveLanguageToFile($file . $extraExtension, $categories);
            }
        }
    }

    /**
     * @param string $file
     * @return array `[$category => [$message => $translation]]`
     * @throws SourceDataException
     */
    private function loadLanguageFromFile($file)
    {
        $result = [];

        foreach ($this->loadJsonFile($file) as $category => $messages) {
            if (!is_array($messages)) {
                throw new SourceDataException("Category `$category` does not contain an Object with translations");
            }

            foreach ($messages as $message => $translation) {
                if (!is_string($translation)) {
                    throw new SourceDataException("Translation for category `$category` message `$message` is not a string");
                }
            }

            $result[$this->stripCategoryPrefix($category)] = $messages;
        }

        return $result;
    }

    /**
     * @param string $file
     * @param array $data
     */
    private function saveLanguageToFile($file, $data)
    {
        $out = [];
        foreach ($data as $category => $messages) {
            $out[$this->categoryPrefix . $category] = $messages;
        }
        $this->saveJsonFile($file, $out);
    }

    /**
     * Update existing translations
     * @param array $existing Old source langauge to update
     * @param array $new New data to update with
     * @return array New array with same keys as `$existing` was
     */
    private function updateTranslationsArray($existing, $new)
    {
        // first try to "copy" source array to optimize in case of no changes
        $result = $existing;

        foreach ($existing as $category => $messages) {
            if (isset($new[$category])) {
                $new_category = $new[$category];
                foreach ($messages as $message => $translation) {
                    if (isset($new_category[$message]) && '' !== $new_category[$message]) {
                        $result[$category][$message] = $new_category[$message];
                    }
                }
            }
        }

        return $result;
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
