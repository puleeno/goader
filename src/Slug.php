<?php
namespace Puleeno\Goader;


class Slug
{
    protected static $instance;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Slugify();
        }
        return self::$instance;
    }

    public static function __callStatic($name, $args)
    {
        if (method_exists(self::$instance, $name)) {
            return call_user_func_array(
                array(self::$instance, $name),
                $args
            );
        } else {
            throw new \Exception('Method %s::%s is not defined', __CLASS__, $name);
        }
    }
}
