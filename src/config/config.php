<?php

return array(
    /*
    |--------------------------------------------------------------------------
    | Available locales
    |--------------------------------------------------------------------------
    |
    | A array list with available locales to load
    |
    | Default locale will the first in array list
    |
    */

    'locales' => ['es_ES', 'en_US'],

    /*
    |--------------------------------------------------------------------------
    | Directories to scan
    |--------------------------------------------------------------------------
    |
    | Set directories to scan to find gettext strings (starting with __)
    |
    */

    'directories' => ['resources'],

    /*
    |--------------------------------------------------------------------------
    | Where the translations are stored
    |--------------------------------------------------------------------------
    |
    | Full path is $storage/xx_XX/LC_MESSAGES/$domain.XX
    |
    */

    'storage' => 'resources/lang/i18n',

    /*
    |--------------------------------------------------------------------------
    | Store files as domain name
    |--------------------------------------------------------------------------
    |
    | Full path is $storage/xx_XX/LC_MESSAGES/$domain.XX
    |
    */

    'domain' => 'messages',

    /*
    |--------------------------------------------------------------------------
    | Cookie name
    |--------------------------------------------------------------------------
    |
    | Locale cookie name. Cookie are stored as plain, without Laravel manager
    |
    */

    'cookie' => 'locale'
);