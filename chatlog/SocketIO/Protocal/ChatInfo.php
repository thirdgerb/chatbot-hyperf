<?php


namespace Commune\Chatlog\SocketIO\Protocal;

use Commune\Support\Utils\TypeUtils;

/**
 * @property-read string $title     房间名字
 * @property-read string $scene     对话机器人场景.
 * @property-read string $icon      默认图标
 * @property-read string $session   sessionId. 不可为空.
 * @property-read bool $closable    是否可以关闭.
 * @property-read bool $bot         默认和机器人对话.
 */
class ChatInfo extends ChatlogResProtocal
{
    public static function stub(): array
    {
        return [
            'title' => '',
            'scene' => '',
            'icon' => '',
            'session' => '',
            'closable' => true,
            'bot' => false,
        ];
    }

    public static function createByUserRoom(Room $room, UserInfo $user) : self
    {
        return static::create([
            'title' => mb_substr($user->name . ':' . $room->session, 0, 15),
            'scene' => $room->scene,
            'icon' => 'mdi-account-question',
            'session' => $room->session,
            'closable' => true,
            'bot' => false,
        ]);
    }

    public static function relations(): array
    {
        return [];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        return TypeUtils::requireFields($data, ['title', 'session'])
            ?? parent::validate($data);
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function getEvent(): string
    {
        return 'CHAT_INFO';
    }


}