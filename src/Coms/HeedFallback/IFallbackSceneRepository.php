<?php


namespace Commune\Chatbot\Hyperf\Coms\HeedFallback;


use Commune\Components\HeedFallback\Data\FallbackSceneOption;
use Commune\Components\HeedFallback\Libs\FallbackSceneRepository;
use Commune\Contracts\Log\ExceptionReporter;
use Hyperf\Redis\RedisFactory;

class IFallbackSceneRepository implements FallbackSceneRepository
{

    /**
     * @var RedisFactory
     */
    protected $factory;

    /**
     * @var ExceptionReporter
     */
    protected $reporter;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $poolName;

    /**
     * IFallbackSceneRepository constructor.
     * @param RedisFactory $factory
     * @param ExceptionReporter $reporter
     * @param string $appId
     * @param string $poolName
     */
    public function __construct(
        RedisFactory $factory,
        ExceptionReporter $reporter,
        string $appId,
        string $poolName
    )
    {
        $this->factory = $factory;
        $this->reporter = $reporter;
        $this->appId = $appId;
        $this->poolName = $poolName;
    }


    protected function pipelineKey() : string
    {
        $appId = $this->appId;
        return "commune:$appId:fallback:scene:pipe";
    }

    protected function hashMapKey() : string
    {
        $appId = $this->appId;
        return "commune:$appId:fallback:scene:map";
    }


    /**
     * @return \Redis
     */
    protected function getRedis()
    {
        return $this->factory->get($this->poolName);
    }

    public function count(): int
    {
        $key = $this->pipelineKey();
        $redis = $this->getRedis();
        $len = $redis->lLen($key);
        return is_int($len) ? $len : 0;
    }


    public function push(FallbackSceneOption $option, bool $toPipe = true): bool
    {
        $key = $this->hashMapKey();
        $redis = $this->getRedis();

        $data = $option->toJson();

        $batchId = $option->batchId;
        $success = $redis->hSet($key, $batchId, $data);

        if ($success && $toPipe) {
            $pipeKey = $this->pipelineKey();
            $redis->lPush($pipeKey, $batchId);
        }
        return $success;
    }

    public function find(string $id): ? FallbackSceneOption
    {
        $key = $this->hashMapKey();
        $redis = $this->getRedis();
        $data = $redis->hGet($key, $id);
        if (empty($data)) {
            return null;
        }

        $un = json_decode($data, true);
        if (empty($un)) {
            $redis->hDel($key, $id);
        }

        try {
            return new FallbackSceneOption($un);
        } catch (\Throwable $e) {
            $this->reporter->report($e);
            $redis->hDel($key, $id);
            return null;
        }
    }

    public function pop(): ? FallbackSceneOption
    {
        $key = $this->pipelineKey();
        $redis = $this->getRedis();
        $id = $redis->lPop($key);
        if (empty($id)) {
            return null;
        }
        return $this->find($id);
    }

    public function delete(string $id): bool
    {
        $key = $this->hashMapKey();
        $redis = $this->getRedis();
        return $redis->hDel($key, $id);
    }

    public function flush() : void
    {
        $pipeKey = $this->pipelineKey();
        $dataKey = $this->hashMapKey();
        $redis = $this->getRedis();
        $redis->del($pipeKey, $dataKey);
    }

}