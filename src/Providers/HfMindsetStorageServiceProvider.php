<?php


/**
 * Class HfMindsetStorageServiceProvider
 * @package Commune\Chatbot\Hyperf\Providers
 */

namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Blueprint\CommuneEnv;
use Commune\Chatbot\Hyperf\Coms\Storage\HfDBStorageOption;
use Commune\Ghost\Providers\MindsetStorageConfigProvider;

class HfMindsetStorageServiceProvider extends MindsetStorageConfigProvider
{

    public static function stub(): array
    {
        return [
            'mindsetCacheExpire' => 599,
            'resourcePath' => CommuneEnv::getResourcePath(),
            'useFileInitStorage' => true,
            'storage' => [
                'wrapper' => HfDBStorageOption::class,
            ],
        ];
    }

    public function getDefaultScope(): string
    {
        return self::SCOPE_CONFIG;
    }

}