<?php


/**
 * Class RunCommuneCommand
 * @package Commune\Chatbot\Hyperf\Command
 */

namespace Commune\Chatbot\Hyperf\Command;

use Commune\Blueprint\Host;
use Commune\Blueprint\CommuneEnv;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;

/**
 * 在 Hyperf 中启动 Commune Host.
 */
class StartCommuneHost extends Command
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * StartAppCommand constructor.
     */
    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        parent::__construct('commune:start');

    }

    public function configure()
    {
        parent::configure();

        $this->setDescription('start commune chatbot platform');

        $this->addArgument('platform', InputOption::VALUE_REQUIRED, 'platform name');

        $this->addOption(
            'reset',
            'r',
            InputOption::VALUE_NONE,
            'Reset ghost mind set, flush all saved logic'
        );

        $this->addOption(
            'debug',
            'd',
            InputOption::VALUE_NONE,
            'Enable Commune debug mode'
        );
    }


    protected function execute(
        InputInterface $input,
        OutputInterface $output
    )
    {
        $this->prepareEnv($input);

        $container = ApplicationContext::getContainer();
        $host = $this->prepareHost(
            $container,
            $input,
            $output
        );

        $platformName = $input->getArgument('platform') ?? '';

        $error = false;
        if (empty($platformName)) {
            $this->error($output, "argument [platform] is required");
            $error = true;
        }

        $config = $host->getConfig();
        $platformConfig = $config->getPlatformConfig($platformName);

        if (!$error && empty($platformConfig)) {

            $str = "platform [$platformName] not exists!";
            $this->error($output, $str);
            $error = true;
        }

        if ($error) {
            $output->writeln("available platforms: \n");
            $rows = [];
            foreach ($config->platforms as $platformConfig) {
                $id = $platformConfig->getId();
                $title = $platformConfig->getTitle();
                $desc = $platformConfig->getDescription();

                $rows[] = [$id, $title, $desc];
            }

            $this->table(
                $output,
                ['id', 'title', 'desc'],
                $rows
            );

            $output->writeln('use [id] as argument [platform] to boot platform. use -h for more help');
            return SIGTERM;
        }

        $host->run($platformName);

        return 0;
    }


    protected function prepareHost(
        ContainerInterface $container,
        InputInterface $input,
        OutputInterface $output
    ) : Host
    {
        /**
         * @var Host $host
         */
        $host = $container->get(Host::class);

        $host->instance(StartCommuneHost::class, $this);
        $host->instance(InputInterface::class, $input);
        $host->instance(SymfonyStyle::class, $output);
        return $host;
    }

    protected function error(OutputInterface $output, string $message) : void
    {
        $output->writeln("<error>$message</error>");
    }

    protected function prepareEnv(InputInterface $input)  :void
    {
        $resetMind = $input->getOption('reset') ?? false;
        CommuneEnv::defineResetMind($resetMind);

        $debug = $input->getOption('debug') ?? false;
        CommuneEnv::defineDebug($debug);

        CommuneEnv::defineBathPath(env(
            'COMMUNE_BATH_PATH',
            BASE_PATH . "/commune"
        ));

        CommuneEnv::defineConfigPath(env(
            'COMMUNE_BATH_PATH',
            BASE_PATH . "/commune/config"
        ));


        CommuneEnv::defineResourcePath(env(
            'COMMUNE_RESOURCE_PATH',
            BASE_PATH . "/commune/resources"
        ));

        // 与 hyperf 的 runtime 分开, 避免被波及
        CommuneEnv::defineRuntimePath(env(
            'COMMUNE_RUNTIME_PATH',
            BASE_PATH . "/runtime"
        ));

        CommuneEnv::defineLogPath(env(
            'COMMUNE_LOG_PATH',
            BASE_PATH . '/runtime/logs'
        ));

    }


    /**
     * Format input to textual table.
     *
     * @param OutputInterface $output
     * @param array $headers
     * @param $rows
     * @param string $tableStyle
     * @param array $columnStyles
     */
    public function table(
        OutputInterface $output,
        array $headers,
        array $rows,
        string $tableStyle = 'default',
        array $columnStyles = []
    ): void
    {
        $table = new Table($output);

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        $table->render();
    }
}