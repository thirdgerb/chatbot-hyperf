<?php


namespace Commune\Chatbot\Hyperf\Foundation;


use Commune\Blueprint\Configs\HostConfig;
use Commune\Host\IHostConfig;
use Commune\Support\Utils\StringUtils;

class HostConfigFactory
{

    public function __invoke() : HostConfig
    {
        $file = StringUtils::gluePath(BASE_PATH, 'commune/config/host.php');

        $hostConfig = include $file;
        return $hostConfig instanceof HostConfig
            ? $hostConfig
            : new IHostConfig($hostConfig);
    }

}