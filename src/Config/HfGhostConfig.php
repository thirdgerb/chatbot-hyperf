<?php

namespace Commune\Chatbot\Hyperf\Config;

use Commune\Blueprint\Configs\GhostConfig;
use Commune\Chatbot\Hyperf\Coms\HeedFallback\IFallbackSceneRepository;
use Commune\Chatbot\Hyperf\Coms\SpaCyNLU\HFSpaCyNLUClient;
use Commune\Ghost\IGhostConfig;
use Commune\Components;
use Commune\Ghost\Predefined\Join\JoinCmd;
use Commune\Ghost\Predefined\Manager\NLUManagerContext;
use Commune\Kernel\GhostCmd;
use Commune\Ghost\Providers as GhostProviders;
use Commune\Chatbot\Hyperf\Providers as HfProviders;
use Commune\Ghost\Predefined\Intent\Navigation;
use Commune\Kernel\Handlers\IGhostRequestHandler;
use Commune\Blueprint\Kernel\Protocals\GhostRequest;
use Commune\Blueprint\Kernel\Handlers\GhostRequestHandler;
use Commune\Blueprint\NLU\NLUService;
use Commune\Chatbot\Hyperf\Coms\Storage\HfDBStorageOption;
use Commune\Blueprint\CommuneEnv;
use Commune\Components\Markdown\Options\MDGroupOption;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * 机器人逻辑内核的配置.
 *
 * @see GhostConfig
 */
class HfGhostConfig extends IGhostConfig
{

    public static function stub(): array
    {
        return [
            'id' => '',

            'name' => '',

            'providers' => [

                /* process service */

                // 注册 mind set 的基本模块. 目前注册到 host 里.
                 GhostProviders\MindsetServiceProvider::class,
                // GhostProviders\MindsetStorageConfigProvider::class,

                // heed fallback

                HfProviders\HFHeedFallbackRepoProvider::class => [
                    'poolName' => 'default',
                ],

                /* req service */

                // runtime driver
                HfProviders\HfRuntimeDriverProvider::class,

                // clone service
                GhostProviders\ClonerServiceProvider::class,
            ],

            'options' => [],

            'components' => [
                // 内部测试用例
                Components\Demo\DemoComponent::class,
                // 树形结构对话
                Components\Tree\TreeComponent::class,

                // markdown 文库
                Components\Markdown\MarkdownComponent::class => [

                    'reset' => CommuneEnv::isResetRegistry(),
                    'groups' => [
                        MDGroupOption::defaultOption(),
                    ],
                    'docStorage' => null,
                    'docInitialStorage' => null,
                    'sectionStorage' => [
                        'wrapper' => HfDBStorageOption::class,
                    ],
                ],


                // heed fallback
                Components\HeedFallback\HeedFallbackComponent::class => [
                    'strategies' => [
                        Components\HeedFallback\HeedFallbackComponent::defaultStrategy(),

                    ],

                    'storage' => [
                        'wrapper' => HfDBStorageOption::class,
                    ],

                    'sceneRepository' => IFallbackSceneRepository::class,
                ],

                // SpaCy-NLU
                Components\SpaCyNLU\SpaCyNLUComponent::class => [
                    'host' => env('SPACY_NLU_HOST', '127.0.0.1:10830'),
                    'requestTimeOut' => 0.3,

                    'nluModuleConfig' => [
                        'matchLimit' => 5,
                        'threshold' => 0.75,
                        'dataPath' => env('SPACY_NLU_INTENTS_DATA', __DIR__ . '/resources/data/intents.json'),
                    ],
                    'chatModuleConfig' => [
                        'threshold' => 0.75,
                        'dataPath' => env('SPACY_NLU_CHATS_DATA', __DIR__ . '/resources/data/chats.json'),
                    ],

                    'httpClient' => HFSpaCyNLUClient::class,
                ],
            ],

            // request protocals
            'protocals' => [
                [
                    'protocal' => GhostRequest::class,
                    // interface
                    'interface' => GhostRequestHandler::class,
                    // 默认的
                    'default' => IGhostRequestHandler::class,
                ],
            ],

            // 用户命令
            'userCommands' => [
                GhostCmd\GhostHelpCmd::class,
                GhostCmd\User\HelloCmd::class,
                GhostCmd\User\WhoCmd::class,
                GhostCmd\User\QuitCmd::class,
                GhostCmd\User\CancelCmd::class,
                GhostCmd\User\BackCmd::class,
                GhostCmd\User\RepeatCmd::class,
                GhostCmd\User\RestartCmd::class,
                GhostCmd\User\HomeCmd::class,
                JoinCmd::class,
            ],

            // 管理员命令
            'superCommands' => [
                GhostCmd\GhostHelpCmd::class,
                GhostCmd\Super\SpyCmd::class,
                GhostCmd\Super\ScopeCmd::class,
                GhostCmd\Super\ProcessCmd::class,
                GhostCmd\Super\IntentCmd::class,
                GhostCmd\Super\RedirectCmd::class,
                GhostCmd\Super\SceneCmd::class,
                GhostCmd\Super\WhereCmd::class,
            ],

            'comprehendPipes' => [
                NLUService::class,
            ],

            'mindPsr4Registers' => [
            ],

            // session
            'sessionExpire' => 3600,
            'sessionLockerExpire' => 3,
            'maxRedirectTimes' => 255,
            'maxRequestFailTimes' => 3,
            'mindsetCacheExpire' => 600,
            'maxBacktrace' => 3,

            'defaultContextName' => 'md.demo.commune_v2_intro',

            'defaultHeedFallback' =>[
                Components\HeedFallback\Action\HeedFallback::class,
            ],

            'globalContextRoutes' => [
                Navigation\CancelInt::genUcl()->encode(),
                Navigation\RepeatInt::genUcl()->encode(),
                Navigation\QuitInt::genUcl()->encode(),
                Navigation\HomeInt::genUcl()->encode(),
                Navigation\BackwardInt::genUcl()->encode(),
                Navigation\RestartInt::genUcl()->encode(),
                Navigation\WrongInt::genUcl()->encode(),
            ]
        ];

    }
}

