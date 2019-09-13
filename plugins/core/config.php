<?php
use Puleeno\Goader\Clients\Config;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

Hook::add_action('goader_init', function () {
    function register_default_config_command_options($options)
    {
        $command = Command::getCommand();
        foreach ($options as $option => $args) {
            $o = $command->option($option);
            if (!empty($args['alias'])) {
                $o->aka($args['alias']);
            }
            if (!empty($args['required'])) {
                $o->require(true);
            }
            if (!empty($args['description'])) {
                $o->describedAs($args['description']);
            }
        }
    }

    function goader_core_register_config_command($runner, $args)
    {
        if (empty($args)) {
            return $runner;
        }
        $command = array_shift($args);

        if ($command === 'config') {
            $options = array();
            if (count($args) > 1) {
                $options = array(
                    'account' => array(
                        'host' => array(
                            'alias' => 'h',
                            'required' => true,
                            'description' => 'Setting account for host',
                        ),
                    ),
                    'host' => array(
                        'name' => array(
                            'alias' => 'n',
                            'required' => true,
                            'description' => 'The host name',
                        ),
                        'load-cookies' => array(
                            'required' => false,
                            'description' => 'Load cookies.txt into host',
                        ),
                        'load-cookiejar' => array(
                            'required' => false,
                            'description' => 'Load cookiejar to host config',
                        ),
                    ),
                    'core' => array(
                    ),
                );
                if (isset($options[$args[0]])) {
                    $options = $options[$args[0]];
                } else {
                    $options = [];
                }
            }
            register_default_config_command_options($options);
            $config = new Config($args);
            return array($config, 'run');
        }
        return $runner;
    }


    Hook::add_filter('register_goader_command', 'goader_core_register_config_command', 15, 2);
});
