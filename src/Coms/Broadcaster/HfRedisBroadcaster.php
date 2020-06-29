<?php


/**
 * Class RedisFactoryBroadcaster
 * @package Commune\Chatbot\Hyperf\Services\Broadcaster
 */

namespace Commune\Chatbot\Hyperf\Coms\Broadcaster;


use Commune\Support\Swoole\SwooleUtils;
use Hyperf\DB\ConnectionInterface;
use Hyperf\Redis\Pool\RedisPool;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisConnection;
use Hyperf\Redis\Pool\PoolFactory;
use Commune\Blueprint\CommuneEnv;
use Commune\Framework\Messenger\Broadcaster\AbsBroadcaster;
use Hyperf\Redis\RedisFactory;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;

/**
 * 基于 Hyperf Redis 模块实现的广播模块.
 *
 * 广播直接使用 Hyperf 的 Redis 实例, 自己去管理协程上下文.
 *
 * 由于订阅的特殊性,
 * 所以订阅不直接用 hf 的 Redis, 而是用 redis connection.
 */
class HfRedisBroadcaster extends AbsBroadcaster
{
    /**
     * @var PoolFactory
     */
    protected $poolFactory;

    /**
     * @var RedisFactory
     */
    protected $redisFactory;

    /**
     * @var string
     */
    protected $poolName;

    public function __construct(
        PoolFactory $poolFactory,
        RedisFactory $redisFactory,
        LoggerInterface $logger,
        string $poolName,
        array $listeningShells
    )
    {
        $this->poolFactory = $poolFactory;
        $this->redisFactory = $redisFactory;
        $this->poolName = $poolName;

        parent::__construct($logger, $listeningShells);
    }


    public static function makeChannel(string $shellId, string $sessionId) : string
    {
        $sessionId = empty($sessionId) ? '' : "/$sessionId";
        return "commune/$shellId$sessionId";
    }

    protected function getRedis() : Redis
    {
        return $this->redisFactory->get($this->poolName);
    }

    public function doPublish(
        string $shellId,
        string $shellSessionId,
        string $publish
    ): void
    {
        // Hyperf 自己管理 Redis 的连接和释放.
        $redis = $this->getRedis();

        try {

            $shellChan = static::makeChannel($shellId, '');
            // session chan
            $sessionChan = static::makeChannel($shellId, $shellSessionId);

            $redis->publish($shellChan, $publish);
            $redis->publish($sessionChan, $publish);

            if (CommuneEnv::isDebug()) {
                $this->logger->debug(__METHOD__ . " publish $shellChan/$sessionChan: $publish");
            }

        } catch (\Throwable $e) {
            $this->logger->error($e);

        }
    }

    public function doSubscribe(
        callable $callback,
        string $shellId,
        string $shellSessionId = null
    ): void
    {
        $chan = static::makeChannel($shellId, $shellSessionId ?? '');

        while (true) {
            try {
                /**
                 * @var RedisPool $redisPool
                 * @var RedisConnection $redisConnection
                 * @var \Redis $client
                 */
                $redisPool = $this->poolFactory->getPool($this->poolName);
                $redisConnection = $redisPool->get()->getConnection();
                $client = $redisConnection;

                $client->setOption(\Redis::OPT_READ_TIMEOUT, -1);
                $client->subscribe([$chan], function ($redis, $chan, $message) use ($callback) {
                    $callback($chan, $message);
                });

            } catch (\Throwable $e) {
                // 不回收 client.
                $this->logger->error($e);
                $client->close();
            }
        }
    }


}