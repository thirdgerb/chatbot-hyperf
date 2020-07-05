<?php


/**
 * Class HfRuntimeDriver
 * @package Commune\Chatbot\Hyperf\Coms\Runtime
 */

namespace Commune\Chatbot\Hyperf\Coms\Runtime;

use Swoole\Coroutine;
use Commune\Contracts\Cache;
use Commune\Support\Babel\Babel;
use Hyperf\DbConnection\Db;
use Commune\Framework\RuntimeDriver\CachableRuntimeDriver;
use Hyperf\Database\ConnectionInterface;
use Commune\Blueprint\Exceptions\IO\SaveDataException;
use Commune\Blueprint\Ghost\Cloner\ClonerLogger;
use Commune\Blueprint\Ghost\Memory\Memory;
use Commune\Chatbot\Hyperf\Coms\Database\MemoryRepository;
use Commune\Blueprint\Exceptions\IO\LoadDataException;
use Psr\Log\LoggerInterface;

/**
 * Hyperf 实现的 runtime driver.
 */
class HfRuntimeDriver extends CachableRuntimeDriver
{

    /**
     * @var string
     */
    protected $poolName;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var int
     */
    protected $cacheTtl;

    /**
     * HfRuntimeDriver constructor.
     * @param Cache $cache
     * @param ClonerLogger $logger
     * @param string $poolName
     * @param string $tableName
     * @param int $cacheTtl
     */
    public function __construct(
        Cache $cache,
        ClonerLogger $logger,
        string $poolName,
        string $tableName,
        int $cacheTtl
    )
    {
        $this->poolName = $poolName;
        $this->tableName = $tableName;
        $this->cacheTtl = $cacheTtl;
        parent::__construct($cache, $logger);
    }

    public function newConnection() : ConnectionInterface
    {
        return Db::connection($this->poolName);
    }


    public function saveLongTermMemories(
        string $cloneId,
        array $memories
    ): bool
    {
        if (empty($memories)) {
            throw new SaveDataException("no memories data");
        }

        try {

            $connection = $this->newConnection();

            // 开启事务. 记忆部分还是必须全部保存成功, 否则请求要设置为无效.
            $connection->beginTransaction();

            $builder = $connection->table($this->tableName);

            // 保存记忆是同步逻辑, 如果保存失败了需要回滚对话.
            MemoryRepository::saveMemories(
                $builder,
                $memories
            );

            $connection->commit();

            // 用协程来保存缓存.
            $this->coroutineCacheLtMemories($cloneId, $memories);

            return true;

        } catch (\Throwable $e) {

            if (isset($connection)) $connection->rollBack();

            $this->logger->error($e);

            return false;
        }
    }

    protected function coroutineCacheLtMemories(
        string $cloneId,
        array $memories
    ) : void
    {
        Coroutine::create(
            function(Cache $cache, string $cloneId, array $memories, LoggerInterface $logger, int $ttl) {

                try {

                    foreach ($memories as $memory) {
                        /**
                         * @var Memory $memory
                         */
                        $id = $memory->getId();
                        $key = $this->getSessionMemoriesCacheKey($cloneId, $id);
                        $se = Babel::serialize($memory);
                        $cache->set($key, $se, $ttl);
                    }
                } catch (\Throwable $e) {
                    $logger->error($e);
                }
            },
            $this->cache,
            $cloneId,
            $memories,
            $this->logger,
            $this->cacheTtl
        );
    }

    public function findLongTermMemories(string $cloneId, string $memoryId): ? Memory
    {
        try {

            $key = $this->getSessionMemoriesCacheKey($cloneId, $memoryId);
            $se = $this->cache->get($key);

            if (!empty($se)) {
                return Babel::unserialize($se);
            }

            $builder = $this->newConnection()->table($this->tableName);

            return MemoryRepository::findMemory(
                $builder,
                $cloneId,
                $memoryId
            );

        } catch (\Throwable $e) {
            $this->logger->error($e);

            throw new LoadDataException(
                "find memory failed, clone id $cloneId, memory id $memoryId",
                $e
            );
        }
    }


}