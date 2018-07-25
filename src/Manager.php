<?php
namespace VovanVE\Yii2I18nJsonExport;

use VovanVE\Yii2I18nJsonExport\drivers\DriverInterface;
use VovanVE\Yii2I18nJsonExport\drivers\FlatCategoryDriver;
use VovanVE\Yii2I18nJsonExport\drivers\SourceDataException;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\di\Instance;

class Manager extends Component
{
    /**
     * @var bool Whether to overwrite source files on import
     */
    public $overwrite = false;

    /**
     * @var array|DriverInterface[] Array of drivers or its configurations
     */
    public $sourceDrivers = [];

    /**
     * @var array|DriverInterface Config for exporting driver
     */
    public $exportDriver = [];

    /**
     * @var DriverInterface[]
     */
    private $_sourceDrivers;

    /**
     * @var DriverInterface
     */
    private $_exportDriver;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!$this->sourceDrivers) {
            throw new InvalidConfigException('No source drivers defined in "sourceDrivers"');
        }
    }

    /**
     * Read all translations from all source drivers and save then with export driver
     * @throws InvalidConfigException
     * @throws SourceDataException
     * @throws MergeConflictException
     */
    public function export()
    {
        $data = [];

        foreach ($this->getSourceDrivers() as $driver) {
            $data = $this->mergeExportTranslations($data, $driver->loadAllTranslations());
        }

        $this->getExportDriver()->saveAllTranslations($data, false);
    }

    /**
     * Read translations from export driver and update existing messages in files under source drivers
     * @throws InvalidConfigException
     * @throws SourceDataException
     */
    public function import()
    {
        $data = $this->getExportDriver()->loadAllTranslations();

        foreach ($this->getSourceDrivers() as $driver) {
            $driver->saveAllTranslations($data, true, $this->overwrite ? '' : '.new');
        }
    }

    /**
     * @return DriverInterface[]
     * @throws InvalidConfigException
     */
    public function getSourceDrivers()
    {
        if (null === $this->_sourceDrivers) {
            $drivers = [];

            foreach ($this->sourceDrivers as $key => $config) {
                $drivers[$key] = Instance::ensure($config, DriverInterface::class);
            }

            $this->_sourceDrivers = $drivers;
        }

        return $this->_sourceDrivers;
    }

    /**
     * @return DriverInterface
     * @throws InvalidConfigException
     */
    public function getExportDriver()
    {
        if (null === $this->_exportDriver) {
            $config = $this->exportDriver;
            if (is_array($config) && !isset($config['class'])) {
                $config['class'] = FlatCategoryDriver::class;
            }

            /** @var DriverInterface $driver */
            $driver = Instance::ensure($config, DriverInterface::class);
            $this->_exportDriver = $driver;
        }
        return $this->_exportDriver;
    }

    /**
     * @param array $data
     * @param array $translations
     * @return array
     * @throws MergeConflictException
     */
    private function mergeExportTranslations($data, $translations)
    {
        if (!$data) {
            return $translations;
        }

        $result = $data;

        foreach ($translations as $language => $categories) {
            if (!isset($result[$language])) {
                $result[$language] = $categories;
                continue;
            }

            $res_lang = &$result[$language];

            foreach ($categories as $category => $messages) {
                if (!isset($res_lang[$category])) {
                    $res_lang[$category] = $messages;
                    continue;
                }

                $res_category = &$res_lang[$category];

                foreach ($messages as $message => $new_translation) {
                    if (isset($res_category[$message]) && '' !== $res_category[$message]) {
                        if ($new_translation !== $res_category[$message]) {
                            throw new MergeConflictException($language, $category, $message, [
                                $res_category[$message],
                                $new_translation,
                            ]);
                        }
                    } else {
                        $res_category[$message] = $new_translation;
                    }
                }
            }
        }

        return $result;
    }
}
