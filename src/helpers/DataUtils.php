<?php
namespace VovanVE\Yii2I18nJsonExport\helpers;

class DataUtils
{
    /**
     * Sort translations map
     *
     * Returns new translations map sorted by source messages which are stored in keys.
     *
     * When `$emptiesOnTop` is `true`, all empty translations will bubble to top at first:
     *
     * ```php
     * [
     *     // empty translations first, sorted by message
     *     'Bar' => '',
     *     'Ipsum' => '',
     *     // then translated, sorted by message
     *     'Foo' => 'Translation of Foo',
     *     'Lorem' => 'Translation of Lorem',
     * ]
     * ```
     *
     * @param array $translations Input translations to sort
     * @param bool $emptiesOnTop Whether to bubble empty translations to top.
     * @return array Returns new sorted array.
     */
    public static function sortTranslationsMap($translations, $emptiesOnTop = false)
    {
        $result = $translations;

        uksort(
            $result,
            $emptiesOnTop
                ? function ($a, $b) use ($translations) {
                    $a_empty = '' === $translations[$a];
                    $b_empty = '' === $translations[$b];
                    return $b_empty - $a_empty ?: strcasecmp($a, $b);
                }
                : 'strcasecmp'
        );

        return $result;
    }
}
