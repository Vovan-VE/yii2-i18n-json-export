<?php
namespace VovanVE\Yii2I18nJsonExport;

class MergeConflictException extends ExportException
{
    /** @var string */
    public $language;
    /** @var string */
    public $category;
    /** @var string */
    public $message;
    /** @var string[] */
    public $translations;

    /**
     * MergeConflictException constructor.
     * @param string $language
     * @param string $category
     * @param string $message
     * @param string[] $translations
     * @param \Throwable|null $previous
     */
    public function __construct(
        $language,
        $category,
        $message,
        array $translations,
        \Throwable $previous = null
    ) {
        $this->language = $language;
        $this->category = $category;
        $this->message = $message;
        $this->translations = $translations;

        parent::__construct("Conflicting translations in `$language` language for (`$category`, `$message`)", 0, $previous);
    }
}
