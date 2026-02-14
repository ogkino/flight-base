<?php
namespace App\Middleware;

use Flight;

/**
 * 权限中间件
 */
class AuthMiddleware
{
    /**
     * 检查是否登录
     */
    public static function check()
    {
        $token = getHeader('Authorization');
        
        if (!$token) {
            error('未授权，请先登录', 401);
        }
        
        $payload = validateToken($token);
        
        if (!$payload) {
            error('Token无效或已过期', 401);
        }
        
        // 从数据库验证用户是否存在且状态正常
        $db = db();
        $table = $payload['type'] === 'admin' ? 'admin' : 'users';
        
        $user = $db->get($table, '*', [
            'id' => $payload['id'],
            'status' => 1
        ]);
        
        if (!$user) {
            error('用户不存在或已被禁用', 401);
        }

        // 检查过期时间
        $expired_at = $user['expired_at'];
        if ($expired_at && $expired_at < time()) {
            error('用户账号已过期', 401);
        }
        
        // 存储当前用户信息
        Flight::set('currentUser', $user);
        Flight::set('userType', $payload['type']);
        
        return true;
    }
    
    /**
     * 检查管理员权限
     */
    public static function checkAdmin()
    {
        $token = getHeader('Authorization');
        
        // 如果 Header 中没有，尝试从 Cookie 获取（支持页面直接访问）
        if (!$token && isset($_COOKIE['admin_token'])) {
            $token = $_COOKIE['admin_token'];
        }
        
        if (!$token) {
            error('未授权，请先登录', 401);
        }
        
        $payload = validateToken($token);
        
        if (!$payload || $payload['type'] !== 'admin') {
            error('需要管理员权限', 403);
        }
        
        // 验证管理员
        $db = db();
        $admin = $db->get('admin', '*', [
            'id' => $payload['id'],
            'status' => 1
        ]);
        
        if (!$admin) {
            error('管理员不存在或已被禁用', 401);
        }

        // 检查过期时间
        $expired_at = $admin['expired_at'];
        if ($expired_at && $expired_at < time()) {
            error('管理员账号已过期', 401);
        }
        
        Flight::set('currentUser', $admin);
        Flight::set('userType', 'admin');
        Flight::set('admin_id', $admin['id']);  // 用于权限验证
        
        return true;
    }
}
