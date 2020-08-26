<?php


namespace Commune\Chatlog\SocketIO\DTO;

use Commune\Support\Message\AbsMessage;
use Commune\Support\Utils\TypeUtils;

/**
 * @property-read string $title     房间名字
 * @property-read string $scene     对话机器人场景.
 * @property-read string $icon      默认图标
 * @property-read string $session   sessionId. 不可为空.
 * @property-read bool $closable    是否可以关闭.
 * @property-read bool|null $bot    是否和机器人对话. false 表示人工, null 表示不能切换.
 */
class ChatInfo extends AbsMessage
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
            'autoJoin' => false,
        ];
    }

    public function autoJoin(bool $bool) : ChatInfo
    {
        $this->_data['autoJoin'] = $bool;
        return $this;
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

}