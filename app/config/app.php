<?php
/**
 * 应用配置文件
 * 
 * 支持从 .env 文件读取配置
 */

return [
    // 应用名称
    'name' => env('APP_NAME', 'Flight Base'),
    
    // 后台管理系统名称
    'admin_name' => env('ADMIN_NAME', 'Flight Base 管理后台'),
    
    // 应用版本
    'version' => '2.2.0',
    
    // 调试模式
    'debug' => env('APP_DEBUG', true),
    
    // 时区
    'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),
    
    // 默认语言
    'locale' => 'zh-CN',
    
    // Token 过期时间（秒）
    'token_expire' => env('TOKEN_EXPIRE', 7 * 86400),  // 7天
    
    // 日志配置
    'log' => [
        'enabled' => env('LOG_ENABLED', true),
        'path' => __DIR__ . '/../../runtime/logs/',
        'level' => env('LOG_LEVEL', 'info'),  // debug, info, warning, error
    ],
    
    // 跨域配置（也可以单独放在 config/cors.php）
    'cors' => require __DIR__ . '/cors.php',
    
    // 上传配置
    'upload' => [
        'path' => __DIR__ . '/../../public/uploads/',
        'max_size' => 10 * 1024 * 1024,  // 10MB
        'allowed_types' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',  // 图片
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', // 文档
            'zip', 'rar' // 压缩包
        ],
    ],
];
