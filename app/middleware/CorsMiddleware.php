<?php
namespace App\Middleware;

/**
 * 跨域中间件
 */
class CorsMiddleware
{
    /**
     * 处理跨域请求
     */
    public static function handle()
    {
        $config = config('cors');
        
        // 允许的来源
        $allowOrigin = $config['allow_origin'] ?? '*';
        if (is_array($allowOrigin)) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $allowOrigin)) {
                header("Access-Control-Allow-Origin: {$origin}");
            }
        } else {
            header("Access-Control-Allow-Origin: {$allowOrigin}");
        }
        
        // 允许的请求方法
        $allowMethods = $config['allow_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowMethods));
        
        // 允许的请求头
        $allowHeaders = $config['allow_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With'];
        header('Access-Control-Allow-Headers: ' . implode(', ', $allowHeaders));
        
        // 是否允许携带凭证
        if ($config['allow_credentials'] ?? false) {
            header('Access-Control-Allow-Credentials: true');
        }
        
        // 预检请求缓存时间
        $maxAge = $config['max_age'] ?? 86400;
        header("Access-Control-Max-Age: {$maxAge}");
        
        // OPTIONS 请求直接返回
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
