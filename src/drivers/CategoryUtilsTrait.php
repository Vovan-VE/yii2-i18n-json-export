<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

use yii\helpers\StringHelper;

trait CategoryUtilsTrait
{
    /** @var string Category prefix to remove on export */
    public $categoryPrefix = '';

    /**
     * @param string $category Source category
     * @return string
     * @throws SourceDataException
     */
    protected function stripCategoryPrefix($category)
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
