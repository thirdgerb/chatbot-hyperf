<?php


namespace Commune\Chatbot\Hyperf\Platforms\Http;


use Hyperf\HttpServer\Router\Router;

class HfRouter
{

    protected static $callers = [];

    public static function add(\Closure $caller) : void
    {
        self::$callers[] = $caller;
    }

    public static function register(string $serverName) : void
    {
        foreach (self::$callers as $caller) {
            Router::addServer($serverName, $caller);
        }
    }
}