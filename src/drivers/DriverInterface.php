<?php
namespace VovanVE\Yii2I18nJsonExport\drivers;

interface DriverInterface
{
    /**
     * Load all translations data from files into array
     * @return array Returns array like `[$language => [$category => [$message => $translation]]]`
     * @throws SourceDataException
     */
    public function loadAllTranslations();

    /**
     * Save translations data into files from data array
     * @param array $data Data in same structure like `loadAllTranslations()` returns
     * @param bool $onlyExisting Whether to only update existing messages  in existing files
     * @param string $extraExtension Extra tag to output filename
     * @return void
     * @throws SourceDataException
     */
    public function saveAllTranslations($data, $onlyExisting = true, $extraExtension = '');
}
