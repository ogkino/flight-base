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
    
    // 默认语言（找不到 Header/Cookie/参数指定的语言时使用）
    'locale' => 'zh-CN',

    // 支持的语言列表（对应 app/lang/{locale}.php 语言包文件）
    'supported_locales' => ['zh-CN', 'en-US'],

    // 字段级多语言后缀映射（用于 CrudConfig.php 内联多语言，如 'label' + 'label_en'）
    // key：语言代码；value：字段后缀，null 表示默认语言（无需后缀，直接用原字段）
    // 新增语言时，在这里加一行即可，无需改动任何业务代码。详见 docs/I18N.md
    'locale_field_suffixes' => [
        'zh-CN' => null,
        'en-US' => 'en',
    ],
    
    // Token 过期时间（秒）
    'token_expire' => env('TOKEN_EXPIRE', 7 * 86400),  // 7天

    // 管理端 / POS 登录是否校验图形验证码（false 时跳过校验，登录页隐藏验证码）
    'captcha_enabled' => env('ADMIN_CAPTCHA_ENABLED', true),
    
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
