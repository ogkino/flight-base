<?php
namespace App\Api;

/**
 * 认证控制器
 */
class AuthController
{
    /**
     * 用户登录
     */
    public static function login()
    {
        $username = getPost('username');
        $password = getPost('password');
        $type = getPost('type', 'user');  // user 或 admin
        
        if (!$username || !$password) {
            error('用户名和密码不能为空');
            return;
        }
        
        $db = db();
        $table = $type === 'admin' ? 'admin' : 'users';
        
        // 查询用户
        $user = $db->get($table, '*', [
            'username' => $username
        ]);
        
        if (!$user) {
            error('用户不存在');
            return;
        }
        
        // 验证密码
        if (!verifyPassword($password, $user['password'])) {
            error('密码错误');
            return;
        }
        
        // 检查状态
        if ($user['status'] != 1) {
            error('账号已被禁用');
            return;
        }

        // 检查过期时间
        $expired_at = $user['expired_at'];
        if ($expired_at && $expired_at < time()) {
            error('账号已过期');
            return;
        }
        
        // 生成 token
        $user['type'] = $type;
        $token = generateToken($user);
        
        // 更新最后登录时间
        $db->update($table, [
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ], [
            'id' => $user['id']
        ]);
        
        // 记录日志
        writeLog("{$type} {$username} 登录成功", 'info');
        
        success([
            'token' => $token,
            'userInfo' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'] ?? $user['username'],
                'avatar' => $user['avatar'] ?? '',
                'type' => $type
            ]
        ], '登录成功');
    }
    
    /**
     * 获取当前用户信息（需要登录）
     */
    public static function info()
    {
        $user = currentUser();
        
        success([
            'id' => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'] ?? $user['username'],
            'avatar' => $user['avatar'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'created_at' => $user['created_at'] ?? ''
        ]);
    }
    
    /**
     * 退出登录
     */
    public static function logout()
    {
        $user = currentUser();
        writeLog("用户 {$user['username']} 退出登录", 'info');
        
        success([], '退出成功');
    }
}
