<?php

return [

    'default' => 'mysql',

    'connections' => [

        'mysql' => [
            'driver'         => 'mysql',
            'host'           => env('MYSQL_HOST', env('DB_HOST', 'mysql.railway.internal')),
            'port'           => env('MYSQL_PORT', env('DB_PORT', '3306')),
            'database'       => env('MYSQL_DATABASE', env('DB_DATABASE', 'railway')),
            'username'       => env('MYSQL_USER', env('DB_USERNAME', 'root')),
            'password'       => env('MYSQL_PASSWORD', env('DB_PASSWORD', 'dRHjLZTHTJBuvgyhRfFLYaFiOjYAttzy')),
            'unix_socket'    => '',
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false,
            'engine'         => null,
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ],

    ],

    'migrations' => ['table' => 'migrations', 'update_date_on_publish' => true],

    'redis' => [
        'client'  => 'phpredis',
        'default' => ['host' => '127.0.0.1', 'password' => null, 'port' => 6379, 'database' => 0],
    ],

];
