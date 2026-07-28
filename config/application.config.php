<?php

return array(
    'modules' => array(
        'Laminas\InputFilter',
        'Laminas\Filter',
        'Laminas\Router',
        'Laminas\Validator',
        'CpmsCommon',
        'Laminas\ApiTools\Versioning',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
    )
);
