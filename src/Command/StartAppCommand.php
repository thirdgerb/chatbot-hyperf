<?php


/**
 * Class RunCommuneCommand
 * @package Commune\Chatbot\Hyperf\Command
 */

namespace Commune\Chatbot\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 */
class StartAppCommand extends HyperfCommand
{

    /**
     * @var ContainerInterface
     */
    protected $container;

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

        $this->addArgument('platform', null, 'platform name');
    }

    public function handle()
    {
        $this->info('hello world');

        var_dump($this->input->getArgument('platform'));
    }


}