<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you are free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => 'bcrypt',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options for the bcrypt algorithm.
    | This will allow you to control each of the options that are passed to
    | the password_hash function when generating bcrypt password hashes.
    |
    */

    'bcrypt' => [
        'rounds' => max(4, min(31, (int) (env('BCRYPT_ROUNDS') ?: 12))),
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options for the Argon algorithms.
    | This will allow you to control each of the options that are passed to
    | the password_hash function when generating Argon password hashes.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash Passwords On Login
    |--------------------------------------------------------------------------
    |
    | Here you may configure whether passwords should be rehashed on login.
    |
    */

    'rehash_on_login' => true,

];
