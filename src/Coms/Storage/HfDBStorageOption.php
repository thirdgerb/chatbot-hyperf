<?php


/**
 * Class HfDatabaseStorageOption
 * @package Commune\Chatbot\Hyperf\Coms\Storage
 */

namespace Commune\Chatbot\Hyperf\Coms\Storage;


use Commune\Support\Registry\Meta\StorageOption;

/**
 * 基于 Hyperf + db/redis 实现的配置读写.
 *
 * @property-read int $cacheExpire
 */
class HfDBStorageOption extends StorageOption
{
    public static function stub(): array
    {
        return [
            'cacheExpire' => 600,
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public function getDriver(): string
    {
        return HfDBStorageOption::class;
    }


}