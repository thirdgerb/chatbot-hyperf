<?php

namespace Commune\Chatlog\Shell;


use Commune\Shell\IShellConfig;
use Commune\Kernel;
use Commune\Blueprint\Shell\Parser\InputParser;
use Commune\Blueprint\Shell\Render\Renderer;
use Commune\Protocals\HostMsg;
use Commune\Blueprint\Kernel\Protocals;
use Commune\Blueprint\Kernel\Handlers;
use Commune\Shell\Render\TranslatorRenderer;
use Commune\Support\Protocal\ProtocalOption;
use Commune\Shell\Providers\ShellSessionServiceProvider;


class ChatlogShellConfig extends IShellConfig
{

    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',
            'providers' => [
                // shell 请求级服务.
                ShellSessionServiceProvider::class,
            ],
            'options' => [],
            'components' => [],

            /**
             * @see ProtocalOption
             */
            'protocals' => [

                /**
                 * App Request Handler
                 * App 负责处理请求的内核.
                 */

                [
                    'protocal' => Protocals\ShellInputRequest::class,
                    'interface' => Handlers\ShellInputReqHandler::class,
                    // 默认的 handler
                    'default' => Kernel\Handlers\IShellInputReqHandler::class,
                ],
                [
                    'protocal' => Protocals\ShellOutputRequest::class,
                    'interface' => Handlers\ShellOutputReqHandler::class,
                    // 默认的 handler
                    'default' => Kernel\Handlers\IShellOutputReqHandler::class,
                ],

                /**
                 * Api Parser
                 * 负责把输入消息进行转义.
                 */
                [
                    'interface' => InputParser::class,
                    'protocal' => HostMsg::class,
                ],

                /**
                 * Renderer
                 */

                // 默认 handler
                [
                    'protocal' => HostMsg::class,
                    'interface' => Renderer::class,
                    'default' => TranslatorRenderer::class,
                ],

            ],
            'sessionExpire' => 864000,
            'sessionLockerExpire' => 0,
        ];
    }

}