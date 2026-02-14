<?php
/**
 * 数据库配置文件
 * 
 * 支持从 .env 文件读取配置
 */

return [
    'type' => env('DB_TYPE', 'mysql'),
    'host' => env('DB_HOST', 'localhost'),
    'database' => env('DB_NAME', 'your_database'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', 'root'),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => 'utf8mb4_unicode_ci',
    'port' => env('DB_PORT', 3306),
    'prefix' => env('DB_PREFIX', 'og_'),  // 表前缀
    
    // PDO 选项
    'option' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
