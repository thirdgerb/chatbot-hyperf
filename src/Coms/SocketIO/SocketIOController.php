<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;

use Commune\Blueprint\Framework\ProcContainer;
use Commune\Blueprint\Host;
use Commune\Chatlog\SocketIO\Blueprint\ChatlogConfig;
use Commune\Contracts\Log\ExceptionReporter;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Hyperf\WebSocketServer\Sender;

class SocketIOController extends BaseNamespace
{
    const EVENT_METHOD_PREFIX = '_on_';

    /*--- 依赖 ---*/

    /**
     * @var Host
     */
    protected $host;

    /**
     * @var ExceptionReporter
     */
    protected $expReporter;


    /**
     * @var ProcContainer
     */
    protected $container;

    /**
     * @var string[]
     */
    protected $protocals = [];

    public function __construct(
        Host $host,
        Sender $sender,
        SidProviderInterface $sidProvider
    ) {
        $this->host = $host;
        $this->container = $host->getProcContainer();

        parent::__construct($sender,$sidProvider);

        $this->protocals = $this->container->get(ChatlogConfig::class)->protocals;
        // 协议.
        foreach ($this->protocals as $eventName => $handler) {
            $method = self::EVENT_METHOD_PREFIX . $eventName;
            $this->on($eventName, [$this, $method]);
        }
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, self::EVENT_METHOD_PREFIX) !== 0) {
            throw new \LogicException("method $name not exists");
        }

        $event = substr($name, strlen(self::EVENT_METHOD_PREFIX));

        $handlerName = $this->protocals[$event] ?? null;
        if (empty($handlerName) || !is_a($handlerName, EventHandler::class, true)) {
            throw new \LogicException("event $event defined invalid handler $handlerName");
        }

        try {
            /**
             * @var EventHandler $handler
             */
            $handler = $this->container->make($handlerName);
            array_unshift($arguments, $event, $this);
            $handler(...$arguments);

        } catch (\Throwable $e) {
            $this->expReporter->report($e);
        }
    }

}
