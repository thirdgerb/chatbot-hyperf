<?php


/**
 * Class HfTranslatorServiceProvider
 * @package Commune\Chatbot\Hyperf\Providers
 */

namespace Commune\Chatbot\Hyperf\Providers;

use Commune\Blueprint\CommuneEnv;
use Commune\Support\Utils\StringUtils;
use Commune\Chatbot\Hyperf\Coms\Storage\HfDBStorageOption;
use Commune\Contracts\Trans\Translator;
use Commune\Framework\Providers\TranslatorBySymfonyProvider;

class HfTranslatorServiceProvider extends TranslatorBySymfonyProvider
{
    public static function stub(): array
    {
        return [
            'defaultLocale' => Translator::DEFAULT_LOCALE,
            'defaultDomain' => Translator::DEFAULT_DOMAIN,
            'storage' => [
                'wrapper' => HfDBStorageOption::class,
                'config' => [],
            ],
            'initStorage' => null,
            'load' => CommuneEnv::isLoadingResource(),
            'reset' => CommuneEnv::isResetRegistry(),
            'resource' => StringUtils::gluePath(
                CommuneEnv::getResourcePath(),
                'trans'
            )
        ];
    }

}