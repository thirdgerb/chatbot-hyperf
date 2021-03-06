<?php


namespace Commune\Chatbot\Hyperf\Config\Platforms;


use Commune\Framework\Providers\LoggerByMonologProvider;
use Commune\Platform\IPlatformConfig;
use Commune\Platform\Libs\Stdio\StdioClientOption;
use Commune\Platform\Libs\Stdio\StdioTextAdapter;
use Commune\Platform\Shell\Stdio\StdioShellPlatform;
use Commune\Support\Utils\TypeUtils;

class StdioShellPlatformConfig extends IPlatformConfig
{

    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',
            'desc' => '',
            'concrete' => StdioShellPlatform::class,
            'bootShell' => '',
            'bootGhost' => false,
            'providers' => [
                LoggerByMonologProvider::class => [
                    'name' => 'stdio_shell',
                    'forceRegister' => true,
                ],
            ],
            'options' => [
                StdioClientOption::class => [
                    'creatorName' => 'client_test',
                    'adapter' => StdioTextAdapter::class,
                    'salt' => 'test',
                ],
            ],
        ];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        return TypeUtils::requireFields(
                $data,
                ['id', 'name', 'bootShell']
            )
            ?? parent::validate($data);
    }

}