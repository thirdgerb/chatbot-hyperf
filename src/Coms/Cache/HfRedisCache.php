<?php


/**
 * Class RedisFactoryCache
 * @package Commune\Chatbot\Hyperf\Services\Cache
 */

namespace Commune\Chatbot\Hyperf\Coms\Cache;


use Commune\Contracts\Cache;
use Commune\Framework\Cache\Psr16CacheAdapter;
use Commune\Framework\Cache\RedisCacheTrait;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\SimpleCache\CacheInterface;

/**
 * 使用 Hyperf Redis Pool factory 实现的 Cache.
 *
 * Hyperf Redis Factory 的机制是用 redis 连接池提供 redis 实例.
 *
 * 用自己的 Redis 代理 RedisConnection, 在协程中反复获取合适的 Connection.
 * 然后通过 Swoole 的 defer 在协程结束的时候将连接释放回连接池.
 *
 * 因此这个 Cache 可以做成进程级的单例, 并持有一个 Redis 单例.
 * 这种做法会在一个 Session 中频繁地切换 client, 但不会被一个 Session 占用一个 client 过长时间.
 *
 * 目前的问题是, 连接异常的时候...自己 retry, 最终会怎么样.
 * 研究代码花的时间较多, 先信任 Hyperf 的解决方案.
 *
 */
class HfRedisCache implements Cache
{
    use RedisCacheTrait;

    /**
     * @var RedisFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $poolName;

    /**
     * @var string
     */
    protected $prefix;


    /**
     * @var CacheInterface
     */
    protected $psr16;

    /**
     * @var Redis
     */
    protected $client;

    /**
     * RedisFactoryCache constructor.
     * @param RedisFactory $factory
     * @param string $poolName
     * @param string $prefix
     */
    public function __construct(
        RedisFactory $factory,
        string $poolName,
        string $prefix
    )
    {
        $this->factory = $factory;
        $this->poolName = $poolName;
        $this->prefix = $prefix;
    }


    public function parseKey(string $key) : string
    {
        return 'cmu:cache:'.$this->prefix . ':' . $key;
    }

    /**
     * 仅仅持有一个 Redis 实例.
     * @return Redis
     */
    protected function getRedis() : Redis
    {
        return $this->client
            ?? $this->client = $this->factory->get($this->poolName);
    }

    protected function call(string $method, callable $query)
    {
        $redis = $this->getRedis();
        return $query($redis);
    }


    public function getPSR16Cache(): CacheInterface
    {
        return $this->psr16
            ?? $this->psr16 = new Psr16CacheAdapter($this);
    }


}