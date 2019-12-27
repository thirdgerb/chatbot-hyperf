<?php


namespace Commune\Platform\WebApi\Demo;


use Commune\Chatbot\OOHost\Session\Session;
use Commune\Platform\WebApi\Libraries\AbstractAction;
use Commune\Platform\WebApi\Libraries\ApiResult;

class HelloWorldAction extends AbstractAction
{
    public function validateInput(array $input): ? string
    {
        return null;
    }

    public function doHandle(Session $session, array $input): ApiResult
    {
        return new ApiResult(['reply' => 'hello world']);
    }


}