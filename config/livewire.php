<?php

use Filament\Http\Middleware\Authenticate;

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Every Filament page, including the public login page, exposes Livewire's
    | upload handshake. Without the settings below, any visitor can obtain a
    | signed upload URL and store arbitrary files (PHP included) in the
    | temporary directory. Uploads therefore require an authenticated admin
    | and are limited to the image types the CMS actually accepts.
    |
    */

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => [
            'required',
            'file',
            'max:12288',
            'mimes:jpg,jpeg,png,webp,gif',
            'extensions:jpg,jpeg,png,webp,gif',
        ],
        'directory' => null,
        'middleware' => [Authenticate::class, 'throttle:20,1'],
        'preview_mimes' => ['png', 'gif', 'jpg', 'jpeg', 'webp'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
