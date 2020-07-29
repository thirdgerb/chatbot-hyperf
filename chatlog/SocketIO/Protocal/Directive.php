<?php


namespace Commune\Chatlog\SocketIO\Protocal;


/**
 * 服务端下发的命令.
 *
 * @property-read string $name      命令的名称.
 * @property-read string $trigger   触发命令的输入信息.
 * @property-read array $payload    更多的参数.
 * @property-read int $createdAt    创建时间.
 * @property-read int $expiredAt    过期时间.
 */
class Directive extends ChatlogResProtocal
{
    const EVENT = 'DIRECTIVE';

    public static function stub(): array
    {
        return [
            'name' => '',
            'trigger' => '',
            'payload' => [],
            'createdAt' => $now = time(),
            'expiredAt' => $now + 10,
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function getEvent(): string
    {
        return 'DIRECTIVE';
    }


}