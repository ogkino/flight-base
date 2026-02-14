<?php
/**
 * 环境变量读取工具
 * 简单的 .env 文件解析器
 */

/**
 * 加载 .env 文件
 */
function loadEnv($path = null)
{
    if ($path === null) {
        $path = __DIR__ . '/../../.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // 跳过注释
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // 解析键值对
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // 移除引号
            $value = trim($value, '"\'');
            
            // 设置环境变量
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
    
    return true;
}

/**
 * 获取环境变量
 */
function env($key, $default = null)
{
    // 从 $_ENV 获取
    if (isset($_ENV[$key])) {
        return parseEnvValue($_ENV[$key]);
    }
    
    // 从 getenv 获取
    $value = getenv($key);
    if ($value !== false) {
        return parseEnvValue($value);
    }
    
    // 返回默认值
    return $default;
}

/**
 * 解析环境变量值
 */
function parseEnvValue($value)
{
    // 布尔值
    if (strtolower($value) === 'true') {
        return true;
    }
    if (strtolower($value) === 'false') {
        return false;
    }
    
    // null
    if (strtolower($value) === 'null') {
        return null;
    }
    
    // 数字
    if (is_numeric($value)) {
        return strpos($value, '.') !== false ? (float)$value : (int)$value;
    }
    
    return $value;
}
