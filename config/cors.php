<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cấu hình CORS cho Open API
    |--------------------------------------------------------------------------
    |
    | Cho phép các ứng dụng Frontend (NextJS, React...) hoặc App Mobile gọi tới
    | hệ thống Open API từ tên miền khác. Vì Open API xác thực bằng Bearer Token
    | (không dùng cookie/session), nên đặt supports_credentials = false và cho
    | phép mọi origin là an toàn — token vẫn bắt buộc ở mỗi request riêng tư.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
