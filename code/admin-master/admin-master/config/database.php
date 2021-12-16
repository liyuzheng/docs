<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'mysql'   => [
            'driver'      => 'mysql',
            'read'        => db_slaves('DB_READ'),
            'write'       => db_slaves('DB_WRITE'),
            'database'    => env('DB_DATABASE', 'forge'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => env('DB_PREFIX', ''),
            'modes'       => ['NO_ENGINE_SUBSTITUTION', 'STRICT_TRANS_TABLES'],
            //            'strict'      => env('DB_STRICT_MODE', false),
            'engine'      => env('DB_ENGINE', null),
            'sticky'      => env('DB_STICKY', false),
        ],
        'mongodb' => [
            'driver'   => 'mongodb',
            'host'     => [env('MONGODB_HOST', '127.0.0.1')],
            'port'     => env('MONGODB_PORT', 27017),
            'database' => env('MONGODB_DATABASE', 'xiaoquan'),
            'username' => env('MONGODB_USERNAME', 'root'),
            'password' => env('MONGODB_PASSWORD', '123456'),
            'options'  => [
                'database' => env('MONGODB_AUTH_DB', 'admin'),
                //                'replicaSet' => 'replicaSetName'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'phpredis',

        'cluster' => env('REDIS_CLUSTER', false),

        'default' => [
            'host'         => env('REDIS_HOST', '127.0.0.1'),
            'password'     => env('REDIS_PASSWORD', null),
            'port'         => env('REDIS_PORT', 6379),
            'database'     => env('REDIS_DB', 0),
            'read_timeout' => 30,   #读超时时间
            'persistent'   => false,   #是否使用pconnect长连接  默认不使用
        ],

        'queue' => [
            'host'         => env('REDIS_QUEUE_HOST', '127.0.0.1'),
            'password'     => env('REDIS_QUEUE_PASSWORD', null),
            'port'         => env('REDIS_QUEUE_PORT', 6379),
            'database'     => env('REDIS_QUEUE_DB', 0),
            'read_timeout' => 30,
            'persistent'   => false,
        ],

        'cache' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],

    ],

];
