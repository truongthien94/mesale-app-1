<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Upload dir
    |--------------------------------------------------------------------------
    |
    | The dir where to store the images (relative from public)
    |
    */
    'dir' => ['uploads'],

    /*
    |--------------------------------------------------------------------------
    | Filesystem disks (Flysytem)
    |--------------------------------------------------------------------------
    |
    | Define an array of Filesystem disks, which use Flysystem.
    | You can set extra options, example:
    |
    | 'my-disk' => [
    |        'URL' => url('to/disk'),
    |        'alias' => 'Local storage',
    |    ]
    */
    'disks' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    | The default group settings for the elFinder routes.
    |
    */

    'route' => [
        'prefix' => 'elfinder',
        'middleware' => array('web', 'auth', 'active', 'admin'), //Set to null to disable middleware filter
    ],

    /*
    |--------------------------------------------------------------------------
    | Access filter
    |--------------------------------------------------------------------------
    |
    | Filter callback to check the files
    |
    */

    'access' => 'Barryvdh\Elfinder\Elfinder::checkAccess',

    /*
    |--------------------------------------------------------------------------
    | Roots
    |--------------------------------------------------------------------------
    |
    | By default, the roots file is LocalFileSystem, with the above public dir.
    | If you want custom options, you can set your own roots below.
    |
    */

    'roots' => null,

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, together with 'roots' and passed to the Connector.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1
    |
    */

    'options' => array(),
    
    /*
    |--------------------------------------------------------------------------
    | Root Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, together with every root by default.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1#root-options
    |
    */
    'root_options' => array(
        // Chặn upload, xoá, sửa, tạo mới thư mục/tập tin trong elFinder nếu đang ở chế độ Demo nhằm bảo vệ dữ liệu hệ thống
        'disabled' => config('app.demo') ? array('mkdir', 'mkfile', 'upload', 'rm', 'paste', 'rename', 'duplicate', 'edit', 'resize', 'pixlr', 'archive', 'extract') : array(),

        // [BẢO MẬT] Chỉ cho phép tải lên tập tin ảnh, chặn tuyệt đối mọi loại tập tin thực thi/kịch bản
        // (PHP, .htaccess, HTML, SVG...) để ngăn nguy cơ tải mã độc lên webroot dẫn tới RCE.
        'uploadOrder' => array('deny', 'allow'),
        'uploadDeny'  => array('all'),
        'uploadAllow' => array(
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/x-icon',
        ),

        // Chặn tạo/đổi tên sang các tập tin nguy hiểm ngay cả khi vượt qua được bộ lọc mime ở trên.
        'acceptedName' => '/^(?!\.|.*\.(?:php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|exe|bat|htaccess|html?|svg|js)$).+$/i',
    ),

);
