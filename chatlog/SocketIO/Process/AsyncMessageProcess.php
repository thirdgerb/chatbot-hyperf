<?php


namespace Commune\Chatlog\SocketIO\Process;

use Commune\Blueprint\Host;
use Commune\Blueprint\Kernel\Handlers\ShellOutputReqHandler;
use Commune\Blueprint\Kernel\Protocals\ShellOutputRequest;
use Commune\Blueprint\Platform;
use Commune\Blueprint\Shell;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;
use Commune\Chatlog\SocketIO\Coms\EmitterAdapter;
use Commune\Chatlog\SocketIO\Handlers\ChatlogEventHandler;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebAdapter;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;
use Commune\Chatlog\SocketIO\Coms\ChatlogFactory;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Contracts\Messenger\Broadcaster;
use Hyperf\Process\AbstractProcess;
use Hyperf\SocketIOServer\SocketIO;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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

    /**
     * @var Broadcaster
     */
    protected $broadcaster;

    /**
     * @var Shell
     */
    protected $shell;

    /**
     * @var HfSocketIOPlatform
     */
    protected $platform;

    /**
     * @var ChatlogFactory
     */
    protected $factory;

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $host = $container->get(Host::class);
        $this->init($host);
        $this->io = $this->getIO($container);
        /**
         * @var Broadcaster $broadcaster
         */
        $broadcaster = $this
            ->host
            ->getProcContainer()
            ->make(Broadcaster::class);

        $broadcaster->subscribe(
            [$this, 'receiveAsyncRequest'],
            $this->shell->getId(),
            null // shell 的全部都监听.
        );
    }

    protected function init(Host $host)
    {
        $this->host = $host;
        $procContainer = $this->host->getProcContainer();
        $this->expHandler = $procContainer->make(ExceptionReporter::class);
        $this->logger = $procContainer->make(LoggerInterface::class);
        $this->shell = $procContainer->make(Shell::class);
        $this->platform = $procContainer->make(Platform::class);
        $this->factory = $procContainer->make(ChatlogFactory::class);
    }


    public function receiveAsyncRequest(string $chan, ShellOutputRequest $request) : void
    {
        $packer = new ChatlogWebPacker(
            new EmitterAdapter($this->io),
            $request->getTraceId()
        );

        $adapter = $packer->adapt(ChatlogWebAdapter::class, $this->shell->getId());

        $success = $this->platform->onAdapter(
            $packer,
            $adapter,
            ShellOutputReqHandler::class,
            $request
        );

        if (!$success) {
            return;
        }

        ChatlogEventHandler::finishPacker(
            $this->factory->getMessageRepo(),
            $this->shell->getId(),
            $this->factory->getRoomService(),
            $packer,
            $this->platform->getLogger()
        );
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