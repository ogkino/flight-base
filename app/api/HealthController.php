<?php
namespace App\Api;

/**
 * 健康检查控制器
 */
class HealthController
{
    /**
     * 健康检查接口（无需登录）
     */
    public static function check()
    {
        $db = db();
        
        // 检查数据库连接
        $dbStatus = 'ok';
        try {
            $db->query('SELECT 1')->fetchAll();
        } catch (\Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }
        
        success([
            'status' => 'healthy',
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'database' => $dbStatus,
            'version' => config('version'),
            'app' => config('name')
        ], 'System is healthy');
    }
}
