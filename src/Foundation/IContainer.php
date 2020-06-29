<?php


/**
 * Class IContainer
 * @package Commune\Chatbot\Hyperf\Foundation
 */

namespace Commune\Chatbot\Hyperf\Foundation;


use Commune\Container\ContainerContract;
use Commune\Container\ContainerTrait;

/**
 * 临时的父容器. 用于被继承时可以使用 parent::make parent::bound 方法.
 */
class IContainer implements ContainerContract
{
    use ContainerTrait;
}