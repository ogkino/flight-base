<?php
namespace App\api\admin;

use App\middleware\AuthMiddleware;
use App\middleware\CaptchaMiddleware;

/**
 * 管理员认证控制器
 */
class AuthController
{
    /**
     * 管理员登录
     */
    public static function login()
    {
        CaptchaMiddleware::handle();

        $username = getPost('username');
        $password = getPost('password');
        
        if (!$username || !$password) {
            error(lang('auth.username_password_required'));
            return;
        }
        
        $db = db();
        
        // 查询管理员
        $admin = $db->get('admin', '*', [
            'username' => $username
        ]);
        
        if (!$admin) {
            error(lang('auth.admin_not_found'));
            return;
        }
        
        // 验证密码
        if (!verifyPassword($password, $admin['password'])) {
            error(lang('auth.password_error'));
            return;
        }
        
        // 检查状态
        if ($admin['status'] != 1) {
            error(lang('auth.account_disabled'));
            return;
        }

        // 检查过期时间
        $expired_at = $admin['expired_at'];
        if ($expired_at && $expired_at < time()) {
            error(lang('auth.account_expired'));
            return;
        }
        
        // 生成 token
        $admin['type'] = 'admin';
        $token = generateToken($admin);
        
        // 设置 Secure HttpOnly Cookie
        // 只有在 HTTPS 下才启用 Secure 标志，防止本地开发（HTTP）时无法写入 Cookie
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $cookieParams = [
            'expires' => time() + 7 * 86400, // 7天后过期
            'path' => '/',
            'domain' => '', // 默认为当前域名
            'secure' => $isSecure, // HTTPS 仅限安全连接
            'httponly' => true, // 禁止 JS 访问，防 XSS
            'samesite' => 'Strict' // 防 CSRF
        ];
        
        // PHP 7.3+ 支持数组参数，低版本需手动处理，这里假设是现代 PHP 环境
        if (PHP_VERSION_ID >= 70300) {
            setcookie('admin_token', $token, $cookieParams);
        } else {
            setcookie('admin_token', $token, $cookieParams['expires'], $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
        }
        
        // 更新最后登录时间
        $db->update('admin', [
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ], [
            'id' => $admin['id']
        ]);
        
        // 记录日志
        writeLog("管理员 {$username} 登录成功", 'info');
        
        // 处理权限
        $permissions = [];
        $isSuperAdmin = ($admin['id'] == 1);
        
        if ($isSuperAdmin) {
            // 超级管理员，不返回权限数据
            $permissions = null;
        } else {
            $permissions = json_decode($admin['permissions'], true);
            if (empty($permissions)) {
                $permissions = (object)[]; // 空对象，表示无权限
            }
        }
        
        success([
            'token' => $token,
            'permissions' => $permissions,
            'is_super_admin' => $isSuperAdmin, // 明确返回是否超管
            'userInfo' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['nickname'] ?? $admin['username'],
                'avatar' => $admin['avatar'] ?? '',
                'type' => 'admin'
            ]
        ], lang('auth.login_success'));
    }
    
    /**
     * 获取管理员信息
     */
    public static function info()
    {
        AuthMiddleware::checkAdmin();
        
        $admin = currentUser();
        
        success([
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname'] ?? $admin['username'],
            'avatar' => $admin['avatar'] ?? '',
            'email' => $admin['email'] ?? '',
            'phone' => $admin['phone'] ?? '',
            'created_at' => $admin['created_at'] ?? ''
        ]);
    }
    
    /**
     * 退出登录
     */
    public static function logout()
    {
        AuthMiddleware::checkAdmin();
        
        $admin = currentUser();
        writeLog("管理员 {$admin['username']} 退出登录", 'info');
        
        // 清除 Cookie
        setcookie('admin_token', '', time() - 3600, '/');
        
        success([], lang('auth.logout_success'));
    }
    
    /**
     * 修改密码
     */
    public static function changePassword()
    {
        AuthMiddleware::checkAdmin();
        
        $oldPassword = getPost('old_password');
        $newPassword = getPost('new_password');
        $confirmPassword = getPost('confirm_password');
        
        if (!$oldPassword || !$newPassword || !$confirmPassword) {
            error(lang('auth.fill_complete_info'));
        }
        
        if ($newPassword !== $confirmPassword) {
            error(lang('auth.new_password_mismatch'));
        }
        
        if (strlen($newPassword) < 6) {
            error(lang('auth.new_password_too_short'));
        }
        
        $admin = currentUser();
        $db = db();
        
        // 验证旧密码
        $currentAdmin = $db->get('admin', '*', ['id' => $admin['id']]);
        if (!verifyPassword($oldPassword, $currentAdmin['password'])) {
            error(lang('auth.old_password_error'));
        }
        
        // 更新密码
        $result = $db->update('admin', [
            'password' => hashPassword($newPassword)
        ], [
            'id' => $admin['id']
        ]);
        
        if ($result->rowCount() > 0) {
            writeLog("管理员 {$admin['username']} 修改密码", 'info');
            success([], lang('auth.change_password_success'));
        } else {
            error(lang('auth.change_password_failed'));
        }
    }
}
