<?php
namespace Puleeno\Goader;

use voku\helper\Hooks;

class Hook
{
    public static function __callStatic($name, $args)
    {
        $hook = Hooks::getInstance();
        if (method_exists($hook, $name) && is_callable(array($hook, $name))) {
            return call_user_func_array(array($hook, $name), $args);
        } else {
            throw new \Exception('Method %s::%s', __CLASS__, $name);
        }
    }
}
