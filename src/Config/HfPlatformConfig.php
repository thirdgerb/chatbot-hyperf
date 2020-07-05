<?php


/**
 * Class HfPlatformConfig
 * @package Commune\Chatbot\Hyperf\Config
 */

namespace Commune\Chatbot\Hyperf\Config;

use Commune\Blueprint\Configs\PlatformConfig;
use Commune\Platform\IPlatformConfig;

/**
 * 机器人启动平台的配置.
 *
 * @see PlatformConfig
 */
class HfPlatformConfig extends IPlatformConfig
{
    public static function stub(): array
    {
        return [
            'id' => '',
            'concrete' => '',
            'bootShell' => null,
            'bootGhost' => false,
            'providers' => [],
            'options' => [],
        ];
    }

}