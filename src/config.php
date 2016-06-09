<?php

return array(
    'common_config' => array(
        'git_location' => 'reacable_gitlocation_from_server.git',
        'git_branch' => 'master',
        'shared_dirs' => [
            'storage'
        ],
        'shared_files' => [
            '.env'
        ],
        'keep_releases' => 5
    ),

    /*
     * Description
     *
     */
    'servers' => array(
        'localhost' => array(
            'host' => 'localhost',
            'user' => 'root', // Don't use root login in real life
            'password' => null,
            'port' => 22
        ),

        'remote' => array(
            'host' => 'yourdomain.com',
            'user' => 'root', // Don't use root login in real life
            'password' => null,
            'port' => 22,
            'custom_key' => null
        )
    ),

    'configurations' => array(
        'testing' => array(
            'server' => 'localhost',
            'path' => '/Users/ebbe/Documents/udv/deploytest',
            'development' => true
        )
    )
);