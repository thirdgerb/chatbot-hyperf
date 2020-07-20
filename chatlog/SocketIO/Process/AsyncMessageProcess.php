<?php


namespace Commune\Chatlog\SocketIO\Process;

use Commune\Blueprint\Host;
use Commune\Contracts\Log\ExceptionReporter;
use Hyperf\Process\AbstractProcess;
use Hyperf\SocketIOServer\SocketIO;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;

/**
 * 异步广播消息.
 */
class AsyncMessageProcess extends AbstractProcess
{
    /**
     * @var string
     */
    public $name = 'message_broadcast';

    /**
     * @var int
     */
    public $nums = 1;


    /**
     * @var Host
     */
    protected $host;

    /**
     * @var ExceptionReporter
     */
    protected $expHandler;

    /**
     * @var SocketIO
     */
    protected $io;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function handle(): void
    {
//        $container = ApplicationContext::getContainer();
//        $this->host = $container->get(Host::class);
//        $this->init();
//        $this->io = $this->getIO($container);
//
//        $i = 1;
//        while(true) {
//            $this->io->emit('broadcasting', 'hello friends!' . $i);
//            $i ++;
//            Coroutine::sleep(5);
//        }

    }

    protected function init()
    {
        $procContainer = $this->host->getProcContainer();
        $this->expHandler = $procContainer->make(ExceptionReporter::class);
        $this->logger = $procContainer->make(LoggerInterface::class);
    }

    protected function getIO(ContainerInterface $container) :  SocketIO
    {
        return $container->get(SocketIO::class);
    }


    protected function logThrowable(\Throwable $throwable): void
    {
        $this->expHandler->report($throwable);
    }
}