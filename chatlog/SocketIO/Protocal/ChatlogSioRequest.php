<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatbot\Hyperf\Coms\SocketIO\ResponseProtocal;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioRequest;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioResponse;
use Commune\Support\Message\AbsMessage;
use Commune\Support\Utils\TypeUtils;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;

/**
 * @property-read string $event
 * @property-read string $trace
 * @property-read string $token
 * @property-read array $proto
 */
class ChatlogSioRequest extends AbsMessage implements SioRequest, HasIdGenerator
{
    use IdGeneratorHelper;

    protected $temp = [];

    public static function stub(): array
    {
        return [
            'event' => '',
            'trace' => '',
            'token' => '',
            'proto' => [],
        ];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return ChatlogSioRequest
     */
    public function with(string $key, $value) : SioRequest
    {
        $this->temp[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getTemp(string $key)
    {
        return $this->temp[$key] ?? null;
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        return TypeUtils::requireFields($data, ['event', 'trace'])
            ?? parent::validate($data);
    }

    /**
     * @param ChatlogResProtocal $protocal
     * @return ChatlogSioResponse
     */
    public function makeResponse(ResponseProtocal $protocal): SioResponse
    {
        return new ChatlogSioResponse([
            'event' => $protocal->getEvent(),
            'trace' => $this->trace,
            'proto' => $protocal,
        ]);
    }


    public static function relations(): array
    {
        return [];
    }


    public function __set_trace(string $name, string $value) : void
    {
        $value = empty($value) ? $this->createUuId() : $value;
        $this->_data[$name] = $value;
    }


    public function isEmpty(): bool
    {
        return empty($this->_data['proto']);
    }


}