<?php


/**
 * Class HfDbStorage
 * @package Commune\Chatbot\Hyperf\Coms\Storage
 */

namespace Commune\Chatbot\Hyperf\Coms\Storage;


use Commune\Chatbot\Hyperf\Coms\Database\OptionRepository;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Support\Option\Option;
use Hyperf\Database\Query\Builder;
use Commune\Support\Registry\Meta\CategoryOption;
use Commune\Support\Registry\Meta\StorageOption;
use Commune\Support\Registry\StorageDriver;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Log\LoggerInterface;

class HfDBStorageDriver implements StorageDriver
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
        $uuid = static::makeCategoryId($categoryOption, $optionId);

        $redis = $this->newRedis();
        if ($redis->exists($uuid)) {
            return true;
        }

        $builder = $this->newBuilder();
        return OptionRepository::uuidExists($builder, $uuid);
    }

    public function find(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        string $optionId
    ): ? Option
    {
        $uuid = static::makeCategoryId($categoryOption, $optionId);
        $optionClass = $categoryOption->optionClass;

        $redis = $this->newRedis();
        $key = $this->getOptionCacheKey($uuid);

        $se = $redis->get($key);
        if (!empty($se)) {
            return $this->unserializeOption($se, $optionClass);
        }

        $saved = OptionRepository::findOptionByUuid(
            $this->newBuilder(),
            $uuid,
            ['data']
        );

        if (empty($saved)) {
            return null;
        }

        $se = $saved->data;

        $option = $this->unserializeOption($se, $categoryOption->optionClass);
        if (empty($option)) {
            return null;
        }

        if ($storageOption instanceof HfDBStorageOption) {
            $this->cacheOption($uuid, $se, $storageOption);
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
            $success = OptionRepository::saveOption(
                $builder,
                $option,
                $categoryOption->name,
                $id,
                $se
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

        return OptionRepository::deleteByUuid($builder, ...$keys);
    }

    public function eachOption(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): \Generator
    {
        $builder = $this->newBuilder();

        $cateName = $categoryOption->name;
        $optionClass = $categoryOption->optionClass;

        $count = OptionRepository::countCategory($builder, $cateName);
        unset($builder);

        $i = 0;
        while ($i < $count) {
            unset($builder);

            $data = OptionRepository::paginateCategory(
                $builder = $this->newBuilder(),
                $cateName,
                $i,
                1,
                ['data']
            );

            if (empty($data)) {
                break;
            }

            yield $this->unserializeOption(
                $data[0]->data,
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
            function(\stdClass $data) use ($optionClass){
                $se = $data->data;
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
            function(\stdClass $data) {
                return $data->option_id;
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
        $cateName = $categoryOption->name;

        $count = OptionRepository::countCategory($builder, $cateName);
        $i = 0;
        while ($i < $count) {

            $data = OptionRepository::paginateCategory(
                $this->newBuilder(),
                $cateName,
                $i,
                1,
                ['option_id']
            );

            if (empty($data)) {
                break;
            }

            yield $data[0]->option_id;
            $i ++;
        }
    }

    public function paginateIds(
        CategoryOption $categoryOption,
        StorageOption $storageOption,
        int $offset = 0,
        int $limit = 20
    ): array
    {
        $cateName = $categoryOption->name;
        $builder = $this->newBuilder();

        $data = OptionRepository::paginateCategory(
            $builder,
            $cateName,
            $offset,
            $limit,
            ['option_id']
        );

        return array_map(function($data) {
            return $data->option_id;
        }, $data);
    }

    public function flush(
        CategoryOption $categoryOption,
        StorageOption $storageOption
    ): bool
    {
        $builder = $this->newBuilder();

        $categoryName = $categoryOption->name;

        $builder->where('category_name', '=', $categoryName)->delete();

        return true;
    }


}