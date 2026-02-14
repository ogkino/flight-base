<?php
/**
 * 跨域配置文件
 */

return [
    // 允许的来源
    // '*' 表示允许所有来源
    // 或者指定具体的域名数组：['https://example.com', 'https://www.example.com']
    'allow_origin' => '*',
    
    // 允许的请求方法
    'allow_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS'
    ],
    
    // 允许的请求头
    'allow_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin'
    ],
    
    // 是否允许携带凭证（Cookie）
    'allow_credentials' => false,
    
    // 预检请求缓存时间（秒）
    'max_age' => 86400,  // 24小时
];
