<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use VovanVE\Yii2I18nJsonExport\helpers\DataUtils;
use VovanVE\Yii2I18nJsonExport\SourceDataException;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Base storage driver for translations with categories as subdirectories.
 *
 * Translation for message `\Yii::t('category/subcategory', 'Test message')`
 * for `ru-RU` language will be stored in `ru-RU/category/subcategory.{extension}` file
 * and will contain data equivalent to following array:
 *
 * ```php
 * [
 *     "Test message" => "Тестовое сообщение",
 * ]
 * ```
 */
abstract class BaseSubdirCategoryDriver extends Component implements DriverInterface
{
    use CategoryUtilsTrait;

    /** @var string Path to messages root for all languages */
    public $path;

    /** @var string File extension without dot like 'json' */
    public $extension;

    /** @var bool Whether to bubble empty translations to top on save */
    public $sortEmptyFirst = false;

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
        if (null === $this->extension) {
            throw new InvalidConfigException('Option "extension" is required');
        }
    }

    /**
     * @inheritdoc
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
        ]);
        foreach ($files as $file) {
            list ($language, $category) = self::parsePathParts($file, $base_path, $this->extension);
            if (!$language || !$category) {
                continue;
            }

            $translations = $this->loadTranslationsFromFile($file);

            $data[$language][$this->stripCategoryPrefix($category)] = $translations;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function saveAllTranslations($data, $onlyExisting = true, $extraExtension = '')
    {
        $abs_path = \Yii::getAlias($this->path);
        $real_path = realpath($abs_path);
        if (false === $real_path) {
            throw new \RuntimeException("Cannot check path: $abs_path");
        }

        foreach ($data as $language => $categories) {
            foreach ($categories as $category => $messages) {
                $prefixed_category = $this->categoryPrefix . $category;
                $file = join('/', [
                    $real_path,
                    $language,
                    strtr($prefixed_category, '\\', '/') . '.' . $this->extension,
                ]);

                if ($onlyExisting) {
                    if (!is_file($file)) {
                        continue;
                    }

                    $existing = $this->loadTranslationsFromFile($file);
                    $new = $this->updateTranslationsArray($existing, $messages);
                    if ($new !== $existing) {
                        $this->saveTranslations($file . $extraExtension, $new);
                    }
                } else {
                    $this->saveTranslations($file . $extraExtension, $messages);
                }
            }
        }
    }

    /**
     * @param string $file
     * @param array $messages
     */
    private function saveTranslations($file, $messages)
    {
        $this->saveTranslationsToFile(
            $file,
            DataUtils::sortTranslationsMap($messages, $this->sortEmptyFirst)
        );
    }

    /**
     * @param string $file
     * @return array
     * @throws SourceDataException
     */
    abstract protected function loadTranslationsFromFile($file);

    /**
     * @param string $file
     * @param array $messages
     */
    abstract protected function saveTranslationsToFile($file, $messages);


    /**
     * Update existing translations
     * @param array $existing Old source translations to update
     * @param array $new New data to update with
     * @return array New array with same keys as `$existing` was
     */
    private function updateTranslationsArray($existing, $new)
    {
        // first try to "copy" source array to optimize in case of no changes
        $result = $existing;

        foreach ($existing as $message => $translation) {
            if (isset($new[$message]) && '' !== $new[$message]) {
                $result[$message] = $new[$message];
            }
        }

        return $result;
    }

    /**
     * Parse language and category from file path
     * @param string $file Filename to parse
     * @param string $basePath Base path with '/' directory separator and with '/' in the end
     * @param string $extension Base name extension to strip
     * @return string[] `[$language, $category]`
     */
    private static function parsePathParts($file, $basePath, $extension)
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
                return [null, null];
            }
        }

        // "ru-RU/foo/bar" => ["ru-RU", "foo/bar"]
        // "ru-RU"         => ["ru-RU", ""]
        return explode('/', $sub_path, 2) + [1 => ''];
    }
}
