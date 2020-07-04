<?php


/**
 * Class HfDbStorage
 * @package Commune\Chatbot\Hyperf\Coms\Storage
 */

namespace Commune\Chatbot\Hyperf\Coms\Storage;


use Commune\Contracts\Log\ExceptionReporter;
use Commune\Support\Option\Option;
use Hyperf\Database\Query\Builder;
use Commune\Support\Registry\Meta\CategoryOption;
use Commune\Support\Registry\Meta\StorageOption;
use Commune\Support\Registry\Storage;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Log\LoggerInterface;

class HfDBStorage implements Storage
{

    /**
     * @var RedisFactory
     */
    protected $redisFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ExceptionReporter
     */
    protected $reporter;

    /**
     * @var string
     */
    protected $connectionPoolName;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $redisPoolName;

    /**
     * HfDatabaseStorage constructor.
     * @param RedisFactory $redisFactory
     * @param LoggerInterface $logger
     * @param ExceptionReporter $reporter
     * @param string $connectionPoolName
     * @param string $tableName
     * @param string $redisPoolName
     */
    public function __construct(
        RedisFactory $redisFactory,
        LoggerInterface $logger,
        ExceptionReporter $reporter,
        string $connectionPoolName,
        string $tableName,
        string $redisPoolName
    )
    {
        $this->redisFactory = $redisFactory;
        $this->logger = $logger;
        $this->reporter = $reporter;
        $this->connectionPoolName = $connectionPoolName;
        $this->tableName = $tableName;
        $this->redisPoolName = $redisPoolName;
    }


    /*------ libs ------*/

    public function newBuilder() : Builder
    {
        return Db::connection($this->connectionPoolName)
            ->table($this->tableName);
    }

    public function newRedis() : Redis
    {
        return $this->redisFactory->get($this->redisPoolName);
    }

    public static function makeCategoryId(CategoryOption $option, string $optionId) : string
    {
        $name = $option->name;
        $class = $option->optionClass;
        return md5("cate:$name:class:$class:opt:$optionId");
    }

    public function unserializeOption(string $data, string $optionClass) : ? Option
    {

        try {
            $json = json_decode($data, true);
            if (!is_array($json)) {
                return null;
            }

            return new $optionClass($json);

        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->reporter->report($e);
            return null;
        }
    }

    public function serializeOption(Option $option) : string
    {
        $arr = $option->toArray();
        return json_encode($arr);
    }

    public function getOptionCacheKey(string $uuid) : string
    {
        return "cmu:hf:option:$uuid:cache";
    }

    public function cacheOption(
        string $uuid,
        string $serialized,
        HfDBStorageOption $option
    ) : void
    {

        $ttl = $option->cacheExpire;

        $redis = $this->newRedis();
        $key = $this->getOptionCacheKey($uuid);

        if (empty($ttl)) {
            $redis->set($key, $serialized);
        } else {
            $redis->setex($key, $ttl, $serialized);
        }
    }

    /*------ repo ------*/

    public function boot(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): void
    {
        return;
    }

    public function has(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        string $optionId
    ): bool
    {

        $id = static::makeCategoryId($categoryOption, $optionId);

        $redis = $this->newRedis();
        if ($redis->exists($id)) {
            return true;
        }

        $builder = $this->newBuilder();
        return $builder->where('uuid', '=', $id)->exists();
    }

    public function find(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        string $optionId
    ): ? Option
    {
        $id = static::makeCategoryId($categoryOption, $optionId);
        $optionClass = $categoryOption->optionClass;

        $redis = $this->newRedis();
        $key = $this->getOptionCacheKey($id);

        $se = $redis->get($key);
        if (!empty($se)) {
            return $this->unserializeOption($se, $optionClass);
        }

        $builder = $this->newBuilder();
        $saved = $builder->where('uuid', '=', $id)->first(['data']);
        $se = $saved['data'] ?? [];

        $option = $this->unserializeOption($se, $categoryOption->optionClass);
        if (empty($option)) {
            return null;
        }

        if ($storageOption instanceof HfDBStorageOption) {
            $this->cacheOption($id, $se, $storageOption);
        }

        return $option;
    }




    public function count(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): int
    {
        $builder = $this->newBuilder();

        $name = $categoryOption->name;

        return $builder
            ->where('category_name', '=', $name)
            ->count();
    }

    public function save(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        Option $option,
        bool $notExists = false
    ): bool
    {
        $optionId = $option->getId();

        $id = static::makeCategoryId($categoryOption, $optionId);

        $se = $this->serializeOption($option);

        $builder = $this->newBuilder();

        try {
            $success = $builder->updateOrInsert(
                ['uuid' => $id],
                [
                    'uuid' => $id,
                    'option_id' => $option->getId(),
                    'title' => $option->getTitle(),
                    'desc' => $option->getDescription(),
                    'category_name' => $categoryOption->name,
                    'data' => $se
                ]
            );

        } catch (\Throwable $e) {
            $this->reporter->report($e);
            $this->logger->error($e->getMessage());
            return false;
        }

        if ($success && $storageOption instanceof HfDBStorageOption) {
            $this->cacheOption($id, $se, $storageOption);
        }

        return $success;
    }

    public function delete(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        string $id,
        string ...$ids
    ): int
    {
        array_unshift($ids, $id);

        $uuIds = array_map(function(string $id) use ($categoryOption) {
            return static::makeCategoryId($categoryOption, $id);
        }, $ids);

        $keys = array_map(function(string $uuid) {
            return $this->getOptionCacheKey($uuid);
        }, $uuIds);

        $redis = $this->newRedis();
        $redis->del(...$keys);

        $builder = $this->newBuilder();

        $deleted = $builder->whereIn('uuid', $uuIds)->delete();
        return $deleted;
    }

    public function eachOption(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): \Generator
    {
        $builder = $this->newBuilder();

        $name = $categoryOption->name;
        $optionClass = $categoryOption->optionClass;

        $count = $builder->where('category_name', '=', $name)->count();

        $i = 0;
        while ($i < $count) {

            $data = $builder
                ->where('category_name', '=', $name)
                ->orderBy('created_at', 'desc')
                ->offset($i)
                ->limit(1)
                ->first(['data']);

            yield $this->unserializeOption(
                $data['data'],
                $optionClass
            );
        }

    }

    public function findByIds(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        array $ids
    ): array
    {
        $builder = $this->newBuilder();

        $name = $categoryOption->name;

        $collection = $builder
            ->where('category_name', '=', $name)
            ->whereIn('option_id', $ids)
            ->get();

        $optionClass = $categoryOption->optionClass;
        return array_map(
            function(array $data) use ($optionClass){
                $se = $data['data'];
                return $this->unserializeOption($se, $optionClass);
            },
            $collection->all()
        );
    }

    public function searchIds(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        string $wildcardId
    ): array
    {
        $len = mb_strlen($wildcardId);

        if ($wildcardId[0] === '*') {
            $wildcardId[0] = '%';
        }

        $last = $len - 1;
        if ($wildcardId[$last] === '*') {
            $wildcardId[$last] = '%';
        }

        $cateName = $categoryOption->name;

        $collection = $this->newBuilder()
            ->where('category_name', '=', $cateName)
            ->where('option_id', 'like', $wildcardId)
            ->orderBy('created_at', 'desc')
            ->get(['option_id']);

        return array_map(
            function(array $data) {
                return $data['option_id'];
            },
            $collection->all()
        );
    }

    public function eachId(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): \Generator
    {
        $builder = $this->newBuilder();

        $name = $categoryOption->name;

        $count = $builder->where('category_name', '=', $name)->count();

        $i = 0;
        while ($i < $count) {

            $data = $builder
                ->where('category_name', '=', $name)
                ->orderBy('created_at', 'desc')
                ->offset($i)
                ->limit(1)
                ->first(['option_id']);

            yield $data['option_id'];
        }
    }

    public function paginateIds(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        int $offset = 0,
        int $limit = 20
    ): array
    {
        $name = $categoryOption->name;
        $builder = $this->newBuilder();

        $collection = $builder
            ->where('category_name', '=', $name)
            ->orderBy('created_at', 'desc')
            ->get('option_id');

        return array_map(function($data) {
            return $data['option_id'];
        }, $collection->all());
    }

    public function flush(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): bool
    {
        $builder = $this->newBuilder();

        $categoryName = $categoryOption->name;

        $builder->where('category_name', '=', $categoryName)
            ->delete();

        return true;
    }


}