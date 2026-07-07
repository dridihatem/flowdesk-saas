<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module package limits (zip upload security)
    |--------------------------------------------------------------------------
    */

    'max_zip_bytes' => (int) env('FLOWDESK_MODULE_MAX_ZIP_BYTES', 15_728_640), // 15 MB

    'max_uncompressed_bytes' => (int) env('FLOWDESK_MODULE_MAX_UNCOMPRESSED_BYTES', 52_428_800), // 50 MB

    'max_files' => (int) env('FLOWDESK_MODULE_MAX_FILES', 250),

    'max_single_file_bytes' => (int) env('FLOWDESK_MODULE_MAX_SINGLE_FILE_BYTES', 5_242_880), // 5 MB

    /*
    |--------------------------------------------------------------------------
    | Allowed top-level folders inside a module archive
    |--------------------------------------------------------------------------
    */

    'allowed_root_folders' => [
        'views',
        'database',
        'assets',
        'lang',
    ],

    /*
    |--------------------------------------------------------------------------
    | Non-PHP asset extensions (under views/, assets/, or module root)
    |--------------------------------------------------------------------------
    */

    'allowed_asset_extensions' => [
        'css', 'js', 'json', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'woff', 'woff2', 'ico', 'map',
    ],

    /*
    |--------------------------------------------------------------------------
    | Path segments that must never appear in archive entries
    |--------------------------------------------------------------------------
    */

    'blocked_path_segments' => [
        '__MACOSX',
        '.git',
        '.svn',
        '.hg',
        'node_modules',
        'vendor',
        '.env',
        '.htaccess',
        '.user.ini',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dangerous filename extensions (executables, scripts, nested archives)
    |--------------------------------------------------------------------------
    */

    'blocked_extensions' => [
        'exe', 'dll', 'so', 'dylib', 'bin', 'com', 'msi', 'app', 'deb', 'rpm',
        'sh', 'bash', 'zsh', 'bat', 'cmd', 'ps1', 'vbs', 'jar', 'war', 'phar', 'phtml',
        'php3', 'php4', 'php5', 'php7', 'php8', 'htaccess', 'htpasswd',
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz',
    ],

];
