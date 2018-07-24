Yii2 I18N JSON Export
=====================

Imagine your project has multiple parts with its own separate I18N translations.
That parts may be for example backend, server side of frontend and client side of frontend.

Of cause, some parts may share some messages, and you sure to provide the same translations
for such shared messages:

```php
// frontend server side
\Yii::t('app/ui', 'Save')
```

```js
// frontend client side
i18n.t('app/ui', 'Save')
```

Solution
--------

1. Use JSON format for translation files for all parts of your project.
1. Export (with merge) all translations from all source parts into single file per each language.
1. Translate messages for specific language in a single JSON file.
1. Import it back to update existing translations in source parts.

Installation
------------

Install through [composer][]:

    composer require vovan-ve/yii2-i18n-json-export

or add to `require` section in your composer.json:

    "vovan-ve/yii2-i18n-json-export": "~1.0.0"

Usage
-----

Assume you use [vovan-ve/yii2-i18n-json-export][] to store translations in
`lang/category/subcategory.json` files.

Add app configuration like following:

```php
'components' => [
    'i18nJsonExport' => [
        'class' => \VovanVE\Yii2I18nJsonExport\components\Manager::class,
        // list of source drivers
        'sourceDrivers' => [
            [
                // JSON translations by https://github.com/vovan-ve/yii2-i18n-json-export
                'class' => \VovanVE\Yii2I18nJsonExport\drivers\SubdirCategoryDriver::class,
                'path' => '@app/messages',
                // strip category prefix
                //'categoryPrefix' => 'app/',
            ],
        ],
        'exportDriver' => [
            'class' => \VovanVE\Yii2I18nJsonExport\drivers\FlatCategoryDriver::class,
            'path' => '@app/i18n',
        ],
        // whether to import back in same files
        //'overwrite' => true,
    ],
],
```

Use component for example from CLI controller:

```php
// @app/commands/I18nDumpController.php:
<?php
namespace app\commands;

use VovanVE\Yii2I18nJsonExport\components\Manager;
use yii\console\Controller;
use yii\di\Instance;

class I18nDumpController extends Controller
{
    public function actionExport()
    {
        $this->getManager()->export();
    }

    public function actionImport()
    {
        $this->getManager()->import();
    }

    /** @var Manager */
    private $manager;

    /**
     * @return Manager
     */
    private function getManager()
    {
        return $this->manager ?? (
            $this->manager = Instance::ensure('i18nJsonExport', Manager::class)
        );
    }
}
```

You are ready:

```sh
$ cd /project

# assume you has already extracted messages under ./messages/
$ cat ./messages/ru-RU/category/subcategory.json
{
    "Test message": ""
}

# export to outsource
$ ./yii i18n-dump/export

# see the result
$ cat ./i18n/ru-RU.json
{
    "category/subcategory": {
        "Test message": ""
    }
}

# translate it like so
$ cat ./i18n/ru-RU.json
{
    "category/subcategory": {
        "Test message": "Тестовое сообщение"
    }
}

# import back
$ ./yii i18n-dump/import

# see new file
# notice `.new` in the end which is covered by default 'overwrite' => true in Manager
$ cat ./messages/ru-RU/category/subcategory.json.new
{
    "Test message": "Тестовое сообщение"
}
```

License
-------

This package is under [MIT License][mit]


[composer]: http://getcomposer.org/
[mit]: https://opensource.org/licenses/MIT
[vovan-ve/yii2-i18n-json-export]: https://github.com/vovan-ve/yii2-i18n-json-export
