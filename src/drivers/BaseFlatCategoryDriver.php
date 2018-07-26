<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use VovanVE\Yii2I18nJsonExport\helpers\DataUtils;
use VovanVE\Yii2I18nJsonExport\SourceDataException;
use yii\base\Component;
use yii\base\InvalidConfigException;

abstract class BaseFlatCategoryDriver extends Component implements DriverInterface
{
    use CategoryUtilsTrait;
    use JsonDriverTrait;

    /** @var string Path to messages root for all languages */
    public $path;

    /** @var string File extension without dot like 'json' */
    public $extension = 'json';

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
    }

    /**
     * @param string $file
     * @return array `[$category => [$message => $translation]]`
     * @throws SourceDataException
     */
    protected function loadLanguageFromFile($file)
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
    protected function saveLanguageToFile($file, $data)
    {
        $out = [];
        $data_sorted = $data;
        ksort($data_sorted);
        foreach ($data_sorted as $category => $messages) {
            $out[$this->categoryPrefix . $category] = DataUtils::sortTranslationsMap(
                $messages,
                $this->sortEmptyFirst
            );
        }
        $this->saveJsonFile($file, $out);
    }

    /**
     * Update existing translations
     * @param array $existing Old source langauge to update
     * @param array $new New data to update with
     * @return array New array with same keys as `$existing` was
     */
    protected function updateTranslationsArray($existing, $new)
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
     * Fill new empty translations by old non-empty
     * @param array $new New translations to fill
     * @param array $old Old translations as fallback
     * @return array
     */
    protected function fillTranslationsArray($new, $old)
    {
        // first try to "copy" source array to optimize in case of no changes
        $result = $new;

        foreach ($new as $category => $messages) {
            if (isset($old[$category])) {
                $old_category = $old[$category];
                foreach ($messages as $message => $translation) {
                    if ('' === $translation && isset($old_category[$message])) {
                        $old_translation = $old_category[$message];
                        if ('' !== $old_translation) {
                            $result[$category][$message] = $old_translation;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
