<?php
namespace App\Api\Admin;

use Flight;
use App\Middleware\AuthMiddleware;

/**
 * 管理员管理控制器
 * 
 * 功能：
 * - 管理员 CRUD
 * - 权限配置
 * - 禁止删除 ID=1 的超级管理员
 */
class AdminManageController
{
    /**
     * 管理员列表
     */
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        
        // 权限验证
        checkPermission('admins', 'list');
        
        $db = db();
        
        $page = (int)getQuery('page', 1);
        $limit = (int)getQuery('limit', 10);
        $keyword = getQuery('keyword', '');
        
        $where = [];
        if ($keyword) {
            $where['OR'] = [
                'username[~]' => $keyword,
                'nickname[~]' => $keyword,
                'email[~]' => $keyword
            ];
        }
        
        $count = $db->count('admin', $where);
        
        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $where['ORDER'] = ['id' => 'ASC'];
        
        $admins = $db->select('admin', [
            'id',
            'username',
            'nickname',
            'email',
            'phone',
            'permissions',
            'status',
            'last_login_time',
            'created_at',
            'expired_at',
        ], $where);
        
        // 处理权限显示
        foreach ($admins as &$admin) {
            if ($admin['id'] == 1) {
                $admin['permissions_text'] = '超级管理员（所有权限）';
            } else {
                $permissions = json_decode($admin['permissions'], true);
                if (empty($permissions)) {
                    $admin['permissions_text'] = '无权限';
                } else {
                    $count = count($permissions);
                    $admin['permissions_text'] = "{$count} 个模块权限";
                }
            }
        }
        
        layuiTable($admins, $count);
    }
    
    /**
     * 创建管理员
     */
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        
        // 权限验证
        checkPermission('admins', 'create');
        
        $db = db();
        
        $username = cleanInput(getPost('username'));
        $password = getPost('password');
        $nickname = cleanInput(getPost('nickname'));
        $email = getPost('email', '');
        $phone = getPost('phone', '');
        $permissions = getPost('permissions', '');
        $status = (int)getPost('status', 1);  // 获取状态，默认为启用
        
        // 验证
        if (empty($username)) {
            error('用户名不能为空');
            return;
        }
        
        if (!validateUsername($username)) {
            error('用户名格式不正确（4-20位字母、数字、下划线）');
            return;
        }
        
        if (empty($password)) {
            error('密码不能为空');
            return;
        }
        
        if (!validatePassword($password)) {
            error('密码格式不正确（6-20位，包含字母和数字）');
            return;
        }
        
        // 检查用户名是否存在
        $exists = $db->has('admin', ['username' => $username]);
        if ($exists) {
            error('用户名已存在');
            return;
        }
        
        // 加密密码
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 插入数据
        $data = [
            'username' => $username,
            'password' => $hashedPassword,
            'nickname' => $nickname,
            'email' => $email,
            'phone' => $phone,
            'permissions' => $permissions,  // 直接保存 JSON 字符串
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'expired_at' => getPost('expired_at', 0),
        ];
        
        $result = $db->insert('admin', $data);
        
        if ($result->rowCount() > 0) {
            auditLog('创建管理员：' . $username);
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
    
    /**
     * 更新管理员
     */
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        
        // 权限验证
        checkPermission('admins', 'update');
        
        $db = db();
        
        // 检查管理员是否存在
        $admin = $db->get('admin', '*', ['id' => $id]);
        if (!$admin) {
            error('管理员不存在', 404);
            return;
        }
        
        $data = [];
        
        // 基本信息（所有管理员都可以修改）
        $nickname = cleanInput(getPost('nickname', ''));
        if ($nickname !== '') {
            $data['nickname'] = $nickname;
        }
        
        $email = getPost('email', '');
        if ($email !== '') {
            $data['email'] = $email;
        }
        
        $phone = getPost('phone', '');
        if ($phone !== '') {
            $data['phone'] = $phone;
        }
        
        // 密码（留空表示不修改）
        $password = getPost('password', '');
        if ($password !== '') {
            if (validatePassword($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            } else {
                error('密码格式不正确（6-20位，包含字母和数字）');
                return;
            }
        }
        
        // ID=1 的超级管理员不允许修改权限和状态
        if ($id != 1) {
            // 允许修改权限（注意：空字符串也要保存）
            // 修复：使用 getPost() 获取数据，而不是 $_POST (因为可能是 JSON 请求)
            $permissions = getPost('permissions');
            if ($permissions !== null) {
                $data['permissions'] = $permissions;
            }
            
            // 允许修改状态
            $status = getPost('status');
            if ($status !== null) {
                $data['status'] = (int)$status;
            }

            // 允许修改过期时间
            $expired_at = getPost('expired_at', 0);
            if ($expired_at !== 0) {
                $data['expired_at'] = $expired_at;
            }
        }
        
        if (empty($data)) {
            error('没有需要更新的内容');
            return;
        }
        
        $db->update('admin', $data, ['id' => $id]);
        
        auditLog('更新管理员 ID: ' . $id);
        success([], '更新成功');
    }
    
    /**
     * 删除管理员
     */
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        
        // 权限验证
        checkPermission('admins', 'delete');
        
        $db = db();
        
        // 禁止删除 ID=1 的超级管理员
        if ($id == 1) {
            error('禁止删除超级管理员', 403);
            return;
        }
        
        // 检查管理员是否存在
        $admin = $db->get('admin', ['id', 'username'], ['id' => $id]);
        if (!$admin) {
            error('管理员不存在', 404);
            return;
        }
        
        $db->delete('admin', ['id' => $id]);
        
        auditLog('删除管理员：' . $admin['username']);
        success([], '删除成功');
    }
    
    /**
     * 获取权限配置选项
     */
    public static function getPermissionOptions()
    {
        AuthMiddleware::checkAdmin();
        
        $permissions = getAllPermissions();
        
        success($permissions);
    }
}
