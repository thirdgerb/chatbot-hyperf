<?php


/**
 * Class RunCommuneCommand
 * @package Commune\Chatbot\Hyperf\Command
 */

namespace Commune\Chatbot\Hyperf\Command;

use Commune\Blueprint\CommuneEnv;
use Commune\Blueprint\Configs\HostConfig;
use Commune\Blueprint\Framework\ProcContainer;
use Commune\Blueprint\Host;
use Commune\Chatbot\Hyperf\Coms\Console\SymfonyStyleConsole;
use Commune\Chatbot\Hyperf\Config\HfHostConfig;
use Commune\Chatbot\Hyperf\Foundation\HfProcessContainer;
use Commune\Contracts\Log\ConsoleLogger;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Framework\ExpReporter\ConsoleExceptionReporter;
use Commune\Host\IHost;
use Commune\Platform\IPlatformConfig;
use Commune\Support\Utils\StringUtils;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;;

/**
 */
class StartAppCommand extends HyperfCommand
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $coroutine = false;

    /**
     * StartApp constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('commune:start');

    }

    public function configure()
    {
        parent::configure();

        $this->setDescription('start commune chatbot platform');

        $this->addArgument('platform', InputOption::VALUE_REQUIRED, 'platform name');

        $this->addOption(
            'debug',
            'd',
            InputOption::VALUE_NONE,
            'set debug mode'
        );
    }

    public function handle()
    {
        $host = $this->prepareHost();
        $console = $host->getConsoleLogger();

        $platformName = $this->input->getArgument('platform') ?? '';

        if (empty($platformName)) {
            $console->error("platform name is required");
            return;
        }

        $config = $host->getConfig();
        $platformConfig = $config->getPlatformConfig($platformName);

        if (empty($platformConfig)) {

            $str = "platform $platformName not exists!\n";
            $str .= "available : \n";

            foreach ($config->platforms as $platformConfig) {
                $id = $platformConfig->getId();
                $title = $platformConfig->getTitle();
                $desc = $platformConfig->getDescription();

                $str .= "[$id] $title: $desc";
            }

            $console->error($str);
            return;
        }

        $host->run($platformName);
    }


    protected function prepareHost() : Host
    {
        $this->prepareEnv();
        $container = $this->prepareContainer();
        $console = $this->prepareConsole();
        $config = $this->prepareConfig();

        $host = new IHost(
            $config,
            $container,
            null,
            null,
            $console
        );

        return $host;
    }

    protected function prepareConfig() : HostConfig
    {
        $file = StringUtils::gluePath(BASE_PATH, 'commune/config/host.php');

        $hostConfig = include $file;
        return $hostConfig instanceof HostConfig
            ? $hostConfig
            : new HfHostConfig($hostConfig);
    }

    protected function prepareConsole() : ConsoleLogger
    {
        return new SymfonyStyleConsole($this->output);
    }

    protected function prepareContainer() : ProcContainer
    {
        $container = new HfProcessContainer($this->container);

        $container->instance(StartAppCommand::class, $this);
        $container->instance(InputInterface::class, $this->input);
        $container->instance(SymfonyStyle::class, $this->output);

        return $container;
    }

    protected function prepareEnv()  :void
    {
        CommuneEnv::defineResetMind(true);

        CommuneEnv::defineBathPath(BASE_PATH . "/commune");
        CommuneEnv::defineResourcePath(BASE_PATH . "/commune/resources");

        $debug = $this->input->getOption('debug') ?? false;
        CommuneEnv::defineDebug($debug);
    }

}