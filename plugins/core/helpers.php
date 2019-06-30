<?php
function add_action()
{
    $args = func_get_args();
    return call_user_func_array(
        array(Puleeno\Goader\Hook::class, 'add_action'),
        $args
    );
}

function add_filter()
{
    $args = func_get_args();
    return call_user_func_array(
        array(Puleeno\Goader\Hook::class, 'add_filter'),
        $args
    );
}

function do_action()
{
    $args = func_get_args();
    return call_user_func_array(
        array(Puleeno\Goader\Hook::class, 'add_action'),
        $args
    );
}

function apply_filters()
{
    $args = func_get_args();
    return call_user_func_array(
        array(Puleeno\Goader\Hook::class, 'apply_filters'),
        $args
    );
}
