<?php
/**
 * LiveStore - config.php
 * Copy this file to config.php and fill in your values.
 * Never commit secrets.
 */
return [
    'db_host'    => 'localhost',
    'db_name'    => 'livestore',
    'db_user'    => 'root',
    'db_pass'    => '',

    'app_name'   => 'LiveStore',
    'app_url'    => 'http://localhost/livestore',
    'debug'      => true,

    'api_secret' => 'change-this-to-a-random-secret',

    'storage' => [
        'driver' => 'local',

        'local' => [
            'root'     => __DIR__ . '/uploads',
            'base_url' => 'http://localhost/livestore/uploads',
        ],

        's3' => [
            'key'      => '',
            'secret'   => '',
            'bucket'   => '',
            'region'   => 'us-east-1',
            'base_url' => '',
            'acl'      => 'public-read',
        ],

        'r2' => [
            'account_id' => '',
            'key'        => '',
            'secret'     => '',
            'bucket'     => '',
            'base_url'   => '',
        ],
    ],
];
