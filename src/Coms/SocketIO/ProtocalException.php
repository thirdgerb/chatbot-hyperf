<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;


use Commune\Blueprint\Exceptions\CommuneErrorCode;
use Commune\Blueprint\Exceptions\CommuneRuntimeException;
use Throwable;

class ProtocalException extends CommuneRuntimeException
{

    public function __construct(string $message = "", Throwable $previous = null)
    {
        parent::__construct($message, CommuneErrorCode::BAD_REQUEST, $previous);
    }

}