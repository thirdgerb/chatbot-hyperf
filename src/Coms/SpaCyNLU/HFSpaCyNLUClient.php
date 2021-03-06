<?php


namespace Commune\Chatbot\Hyperf\Coms\SpaCyNLU;


use Commune\Components\SpaCyNLU\Impl\GuzzleSpaCyNLUClient;
use Hyperf\Guzzle\CoroutineHandler;
use GuzzleHttp\HandlerStack;

class HFSpaCyNLUClient extends GuzzleSpaCyNLUClient
{

    protected function getClientOption(): array
    {
        $option = parent::getClientOption(); // TODO: Change the autogenerated stub
        $option['handler'] = HandlerStack::create(new CoroutineHandler());
        return $option;
    }

}