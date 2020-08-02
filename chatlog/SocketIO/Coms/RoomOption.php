<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatlog\SocketIO\Coms\Room\DefaultOnInput;
use Commune\Chatlog\SocketIO\Coms\Room\DefaultOnOutput;
use Commune\Chatlog\SocketIO\Coms\Room\DefaultOnPacker;
use Commune\Support\Option\AbsOption;

/**
 * 房间的基本配置.
 *
 * ## 给客户端呈现的配置.
 * @property-read string $scene     房间显示的场景. 用于生成 Entry
 * @property-read string $title     房间对外的名称.
 * @property-read string $desc      房间的一句话介绍.
 * @property-read string $icon      预定义的图标.
 * @property-read bool $closable    是否可以关闭.
 * @property-read string $category  房间的分类. 给用户推荐时可以按分类来排列.
 *
 * ## 机器人相关的逻辑
 * @property-read bool $bot         房间默认是否对机器人.
 * @property-read string $botName   房间里机器人的昵称.
 *
 * ## 权限管理.
 * @property-read int $level        加入房间的默认身份级别.
 * @property-read int $levelMode    判断是否有资格加入房间的逻辑.
 * @property-read bool $private     房间如果是私人的, 则只允许管理员和用户自己加入.
 *
 *
 *
 * ## 消息相关.
 * @property-read bool $supervised  表示需要通知给管理员的房间. 任何输入消息都会告知管理员房间的存在.
 *
 * ## sign 相关逻辑. 用户登录时提示的房间.
 * @property-read bool $autoJoin    符合级别的用户是否自动加入房间.
 * @property-read bool $recommend   是否将房间主动推荐给用户 (未连接).
 *
 * @property-read string $entry
 *
 * ## 房间的转化模块
 *
 * @property-read string $onInput
 * @property-read string $onPacker
 * @property-read string $onOutput
 */
class RoomOption extends AbsOption
{
    const IDENTITY = 'scene';

    const LEVEL_MODE_EXACTLY = 0;
    const LEVEL_MODE_ABOVE = 1;
    const LEVEL_MODE_BELOW = 2;

    public static function stub(): array
    {
        return [
            // 房间的基本信息
            'scene' => '',
            'title' => '',
            'desc' => '',
            'icon' => '',
            'category' => '',
            'closable' => true,

            // 机器人相关信息
            'bot' => true,
            'botName' => 'Commune',

            // 权限基本信息.
            'level' => Supervise::GUEST,
            'levelMode' => self::LEVEL_MODE_ABOVE,
            'private' => false,

            // 房间的基础信息
            'supervised' => false,
            'autoJoin' => false,
            'recommend' => false,

            // 房间的自定义组件.
            'onInput' => DefaultOnInput::class,
            'onPacker' => DefaultOnPacker::class,
            'onOutput' => DefaultOnOutput::class,
//            'onDirective' => '',
//            'onIntent' => '',
        ];
    }

    public static function relations(): array
    {
        return [];
    }


}