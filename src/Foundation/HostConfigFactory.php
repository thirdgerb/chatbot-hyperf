<?php


namespace Commune\Chatbot\Hyperf\Foundation;


use Commune\Blueprint\CommuneEnv;
use Commune\Blueprint\Configs\HostConfig;
use Commune\Blueprint\Configs\PlatformConfig;
use Commune\Blueprint\Configs\ShellConfig;
use Commune\Host\IHostConfig;
use Commune\Platform\IPlatformConfig;
use Commune\Shell\IShellConfig;
use Commune\Support\Utils\StringUtils;
use Symfony\Component\Finder\Finder;

class HostConfigFactory
{

    public function __invoke() : HostConfig
    {
        $configPath = CommuneEnv::getConfigPath();
        $file = StringUtils::gluePath($configPath, 'host.php');

        $hostConfig = include $file;
        $config = $hostConfig instanceof HostConfig
            ? $hostConfig
            : new IHostConfig($hostConfig);

        // 使用相对路径来定义 shell 与 platform 的配置.
        $shells = $this->loadShells();
        $platforms = $this->loadPlatforms();

        $merging = [
            'shells' => $shells + $config->shells,
            'platforms' => $platforms + $config->platforms,
        ];

        return $config->merge($merging);
    }

    /**
     * 读取 shells 路径下所有的 shell 配置.
     * @return ShellConfig[] 
     */
    protected function loadShells() : array
    {
        $configPath = CommuneEnv::getConfigPath();
        $finder = new Finder();
        $finder = $finder->files()
            ->in(StringUtils::gluePath($configPath, 'shells'))
            ->depth(0)
            ->name('*.php');

        $shells = [];
        foreach($finder as $file) {

            /**
             * @var \SplFileInfo $file
             */
            $basename = $file->getBasename();
            $shellName = str_replace('.php', '', $basename);
            $path = $file->getPathname();
            $data = include $path;
            $shellConfig = $data instanceof ShellConfig
                ? $data
                : new IShellConfig($data);
            $shells[$shellName] = $shellConfig;
        }

        return $shells;
    }

    protected function loadPlatforms() : array
    {
        $configPath = CommuneEnv::getConfigPath();
        $finder = new Finder();
        $finder = $finder->files()
            ->in(StringUtils::gluePath($configPath, 'platforms'))
            ->depth(0)
            ->name('*.php');

        $platforms = [];
        foreach($finder as $file) {

            /**
             * @var \SplFileInfo $file
             */
            $basename = $file->getBasename();
            $platformName = str_replace('.php', '', $basename);
            $path = $file->getPathname();
            $data = include $path;
            $platformConfig = $data instanceof PlatformConfig
                ? $data
                : new IPlatformConfig($data);
            $platforms[$platformName] = $platformConfig;
        }

        return $platforms;
    }


}