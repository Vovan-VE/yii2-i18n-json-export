<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use yii\helpers\Json;
use yii\helpers\StringHelper;

trait JsonDriverTrait
{
    /** @var int Options argument to `json_encode()` on import */
    public $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    /** @var bool */
    public $jsonEndNewLine = true;

    /** @var string Category prefix to remove on export */
    public $categoryPrefix = '';

    /**
     * @param $file
     * @return array
     * @throws SourceDataException
     */
    protected function loadJsonFile($file)
    {
        $content = file_get_contents($file);
        if (false === $content) {
            throw new \InvalidArgumentException('Cannot read file');
        }

        $data = Json::decode($content);
        if (!is_array($data)) {
            throw new SourceDataException('JSON content did is not an Object');
        }
        return $data;
    }

    /**
     * @param string $file
     * @param array $messages
     */
    protected function saveJsonFile($file, $messages)
    {
        $json = Json::encode($messages, $this->jsonOptions);
        if ($this->jsonEndNewLine) {
            $json = rtrim($json) . PHP_EOL;
        }

        if (false === file_put_contents($file, $json, LOCK_EX)) {
            throw new \RuntimeException('Cannot save file');
        }
    }

    /**
     * @param string $category Source category
     * @return string
     * @throws SourceDataException
     */
    private function stripCategoryPrefix($category)
    {
        $prefix = $this->categoryPrefix;
        if ('' === $prefix) {
            return $category;
        }

        if (StringHelper::startsWith($category, $prefix)) {
            return StringHelper::byteSubstr($category, StringHelper::byteLength($prefix));
        }

        throw new SourceDataException('Source category name does not start with the prefix');
    }
}
