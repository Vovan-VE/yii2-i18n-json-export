<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use yii\helpers\VarDumper;

/**
 * Translations storage driver with categories as subdirectories ith PHP files.
 *
 * Translation for message `\Yii::t('category/subcategory', 'Test message')`
 * for `ru-RU` language will be stored in `ru-RU/category/subcategory.json` file
 * and will contain:
 *
 * ```php
 * <?php
 * return [
 *     "Test message" => "Тестовое сообщение",
 * ];
 * ```
 *
 * This should be similar to `\yii\i18n\PhpMessageSource`.
 * @see \yii\i18n\PhpMessageSource
 */
class SubdirCategoryPhpDriver extends BaseSubdirCategoryDriver
{
    /** @var string File extension without dot like 'php' */
    public $extension = 'php';
    /**
     * @var string File header in generated PHP file with messages.
     */
    public $phpFileHeader = '';
    /**
     * @var string|null DocBlock used for messages array in generated PHP file.
     */
    public $phpDocBlock = '';

    /**
     * @param string $file
     * @return array
     * @throws SourceDataException
     */
    protected function loadTranslationsFromFile($file)
    {
        $data = include $file;

        if (!is_array($data)) {
            throw new SourceDataException("File must to return an array");
        }

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
        $array = VarDumper::export($messages);
        $content = <<<EOD
<?php
{$this->phpFileHeader}{$this->phpDocBlock}
return $array;

EOD;

        if (false === file_put_contents($file, $content, LOCK_EX)) {
            throw new \RuntimeException('Cannot save file');
        }
    }
}
