<?php
namespace App\Api\Admin;

use Flight;
use App\Middleware\AuthMiddleware;

/**
 * 仪表盘控制器
 * 
 * 提供统计数据和系统信息
 */
class DashboardController
{
    /**
     * 获取用户总数
     */
    public static function getUsersCount()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        $count = $db->count('users');
        
        success(['count' => $count]);
    }
    
    /**
     * 获取文章总数
     */
    public static function getArticlesCount()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        $count = $db->count('articles');
        
        success(['count' => $count]);
    }
    
    /**
     * 获取今日浏览量
     */
    public static function getTodayViews()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        
        // 查询所有文章的浏览量总和
        $totalViews = $db->sum('articles', 'views') ?? 0;
        
        success(['count' => $totalViews]);
    }
    
    /**
     * 获取系统状态
     */
    public static function getSystemStatus()
    {
        AuthMiddleware::checkAdmin();
        
        $status = [
            'status' => '正常',
            'php_version' => PHP_VERSION,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'uptime' => '运行中'
        ];
        
        success($status);
    }
}
