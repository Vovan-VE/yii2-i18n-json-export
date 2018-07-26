<?php
namespace VovanVE\Yii2I18nJsonExport;

class MergeConflictException extends ExportException
{
    /** @var string */
    public $language;
    /** @var string */
    public $category;
    /** @var string */
    public $sourceMessage;
    /** @var string[] */
    public $translations;

    /**
     * MergeConflictException constructor.
     * @param string $language
     * @param string $category
     * @param string $sourceMessage
     * @param string[] $translations
     * @param \Throwable|null $previous
     */
    public function __construct(
        $language,
        $category,
        $sourceMessage,
        array $translations,
        \Throwable $previous = null
    ) {
        $this->language = $language;
        $this->category = $category;
        $this->sourceMessage = $sourceMessage;
        $this->translations = $translations;

        parent::__construct("Conflicting translations in `$language` language for (`$category`, `$sourceMessage`)", 0, $previous);
    }
}
