<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

/**
 * Translations storage driver with categories as subdirectories with JSON files.
 *
 * Translation for message `\Yii::t('category/subcategory', 'Test message')`
 * for `ru-RU` language will be stored in `ru-RU/category/subcategory.json` file
 * and will contain:
 *
 * ```json
 * {
 *     "Test message": "Тестовое сообщение"
 * }
 * ```
 */
class SubdirCategoryJsonDriver extends BaseSubdirCategoryDriver
{
    use JsonDriverTrait;

    /** @var string File extension without dot like 'json' */
    public $extension = 'json';

    /**
     * @param string $file
     * @return array
     * @throws SourceDataException
     */
    protected function loadTranslationsFromFile($file)
    {
        $data = $this->loadJsonFile($file);

        foreach ($data as $message => $translation) {
            if (!is_string($translation)) {
                throw new SourceDataException("Translation for message `$message` is not a string");
            }
        }

        return $data;
    }

    /**
     * @param string $file
     * @param array $messages
     */
    protected function saveTranslationsToFile($file, $messages)
    {
        $this->saveJsonFile($file, $messages);
    }
}
