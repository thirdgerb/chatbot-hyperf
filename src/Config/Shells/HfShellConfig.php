<?php

namespace Commune\Chatbot\Hyperf\Config\Shells;

use Commune\Kernel;
use Commune\Shell\IShellConfig;
use Commune\Blueprint\Shell\Parser\InputParser;
use Commune\Blueprint\Shell\Render\Renderer;
use Commune\Protocals\HostMsg;
use Commune\Blueprint\Kernel\Protocals;
use Commune\Blueprint\Kernel\Handlers;
use Commune\Shell\Providers\ShellSessionServiceProvider;
use Commune\Shell\Render\SystemIntentRenderer;
use Commune\Shell\Render\TranslatorRenderer;
use Commune\Support\Protocal\ProtocalOption;


/**
 * 机器人所属 Shell 的配置.
 *
 * 例如同一个对话机器人, 其 Shell 可能有:
 * - 微信端
 * - 网页版
 * - 钉钉
 * - 智能音箱
 * - 自建聊天室
 * 等等.
 *
 * 每个 Shell 相当于一个独立的身份, 但是共用同一个逻辑内核 Ghost.
 *
 * Shell 需要在 Platform 上启动. 两者的关系完全取决于架构.
 * 通常一个 Platform 最多启动一个 Shell, 但一个 Shell 可能有多种 Platform.
 */
class HfShellConfig extends IShellConfig
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
                'shellInputReqHandler' => [
                    'protocal' => Protocals\ShellInputRequest::class,
                    'interface' => Handlers\ShellInputReqHandler::class,
                    // 默认的 handler
                    'default' => Kernel\Handlers\IShellInputReqHandler::class,
                ],
                'shellOutputReqHandler' => [
                    'protocal' => Protocals\ShellOutputRequest::class,
                    'interface' => Handlers\ShellOutputReqHandler::class,
                    // 默认的 handler
                    'default' => Kernel\Handlers\IShellOutputReqHandler::class,
                ],

                /**
                 * Api Parser
                 * 负责把输入消息进行转义.
                 */
                'inputParser' => [
                    'protocal' => HostMsg::class,
                    'interface' => InputParser::class,
                ],

                /**
                 * Renderer
                 */
                // 系统命令的 handler
                'systemIntentRenderer' => [
                    'protocal' => HostMsg\IntentMsg::class,
                    'interface' => Renderer::class,
                    'handlers' => [
                        [
                            'handler' => SystemIntentRenderer::class,
                            'filters' => [
                                'system.*'
                            ],
                            'params' => [],
                        ],
                    ],
                    'default' => TranslatorRenderer::class,
                ],

                /**
                 * Renderer
                 */
                // 默认 handler
                'fallbackRender' => [
                    'protocal' => HostMsg::class,
                    'interface' => Renderer::class,
                    'default' => TranslatorRenderer::class,
                ],

            ],
            'sessionExpire' => 3600,
            'sessionLockerExpire' => 0,
        ];
    }


}