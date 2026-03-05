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
     * 非超级管理员只能看到自己创建/管理范围内的账户（不显示超级管理员）
     */
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('admins', 'list');
        
        $db = db();
        $currentAdminId = \Flight::get('admin_id');
        
        $page    = (int)getQuery('page', 1);
        $limit   = (int)getQuery('limit', 10);
        $keyword = getQuery('keyword', '');
        
        $where = [];

        // 非超级管理员：不显示超级管理员（ID=1）
        if ($currentAdminId != 1) {
            $where['id[!]'] = 1;
        }

        if ($keyword) {
            $where['OR'] = [
                'username[~]' => $keyword,
                'nickname[~]' => $keyword,
                'email[~]'    => $keyword,
            ];
        }
        
        $count = $db->count('admin', $where);
        
        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $where['ORDER'] = ['id' => 'ASC'];
        
        $admins = $db->select('admin', [
            'id', 'username', 'nickname', 'email', 'phone',
            'permissions', 'status', 'last_login_time', 'created_at', 'expired_at',
        ], $where);
        
        foreach ($admins as &$admin) {
            if ($admin['id'] == 1) {
                $admin['permissions_text'] = '超级管理员（所有权限）';
            } else {
                $permissions = json_decode($admin['permissions'], true);
                if (empty($permissions)) {
                    $admin['permissions_text'] = '无权限';
                } else {
                    $cnt = count($permissions);
                    $admin['permissions_text'] = "{$cnt} 个模块权限";
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
        checkPermission('admins', 'create');
        
        $db = db();
        $currentAdminId = \Flight::get('admin_id');
        
        $username    = cleanInput(getPost('username'));
        $password    = getPost('password');
        $nickname    = cleanInput(getPost('nickname'));
        $email       = getPost('email', '');
        $phone       = getPost('phone', '');
        $permissions = getPost('permissions', '');
        $status      = (int)getPost('status', 1);
        
        if (empty($username)) { error('用户名不能为空'); return; }
        if (!validateUsername($username)) { error('用户名格式不正确（4-20位字母、数字、下划线）'); return; }
        if (empty($password))  { error('密码不能为空'); return; }
        if (!validatePassword($password)) { error('密码格式不正确（6-20位，包含字母和数字）'); return; }
        
        if ($db->has('admin', ['username' => $username])) {
            error('用户名已存在');
            return;
        }

        // 权限降级校验：非超管只能分配自己拥有的权限
        if ($currentAdminId != 1 && !empty($permissions)) {
            $err = self::validatePermissionSubset($permissions, $currentAdminId);
            if ($err) { error($err, 403); return; }
        }
        
        $result = $db->insert('admin', [
            'username'   => $username,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'nickname'   => $nickname,
            'email'      => $email,
            'phone'      => $phone,
            'permissions'=> $permissions,
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'expired_at' => getPost('expired_at', 0),
        ]);
        
        if ($result->rowCount() > 0) {
            auditLog('创建管理员：' . $username);
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
    
    /**
     * 更新管理员
     * - 非超管不能编辑超级管理员
     * - 非超管只能为被编辑者分配自己拥有的权限
     */
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('admins', 'update');
        
        $db = db();
        $currentAdminId = \Flight::get('admin_id');

        // 非超管禁止编辑超级管理员
        if ($currentAdminId != 1 && $id == 1) {
            error('无权编辑超级管理员', 403);
            return;
        }
        
        $admin = $db->get('admin', '*', ['id' => $id]);
        if (!$admin) {
            error('管理员不存在', 404);
            return;
        }
        
        $data = [];
        
        $nickname = cleanInput(getPost('nickname', ''));
        if ($nickname !== '') $data['nickname'] = $nickname;
        
        $email = getPost('email', '');
        if ($email !== '') $data['email'] = $email;
        
        $phone = getPost('phone', '');
        if ($phone !== '') $data['phone'] = $phone;
        
        $password = getPost('password', '');
        if ($password !== '') {
            if (!validatePassword($password)) {
                error('密码格式不正确（6-20位，包含字母和数字）');
                return;
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        // 超级管理员自身的权限/状态不可修改
        if ($id != 1) {
            $permissions = getPost('permissions');
            if ($permissions !== null) {
                if ($id == $currentAdminId) {
                    // 不允许修改自己的权限：静默跳过该字段，其他字段（昵称、密码等）正常保存
                    // 如需修改权限，须由其他有权限的管理员操作
                } else {
                    // 权限降级校验：非超管只能分配自己拥有的权限
                    if ($currentAdminId != 1 && !empty($permissions)) {
                        $err = self::validatePermissionSubset($permissions, $currentAdminId);
                        if ($err) { error($err, 403); return; }
                    }
                    $data['permissions'] = $permissions;
                }
            }
            
            // 不允许修改自己的状态（防止自我禁用）、权限、过期时间
            if ($id != $currentAdminId) {
                $status = getPost('status');
                if ($status !== null) $data['status'] = (int)$status;

                $expired_at = getPost('expired_at', 0);
                if ($expired_at !== 0) {
                    $data['expired_at'] = $expired_at;
                }
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
    /**
     * 删除管理员
     */
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('admins', 'delete');
        
        $db = db();
        $currentAdminId = \Flight::get('admin_id');

        // 禁止删除超级管理员
        if ($id == 1) {
            error('禁止删除超级管理员', 403);
            return;
        }

        // 禁止删除自己
        if ($id == $currentAdminId) {
            error('不能删除自己的账号', 403);
            return;
        }
        
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
     * - 超级管理员：返回全部权限
     * - 普通管理员：只返回自己拥有的权限（防止越级授权）
     */
    public static function getPermissionOptions()
    {
        AuthMiddleware::checkAdmin();
        
        $allPermissions = getAllPermissions();
        $currentAdminId = \Flight::get('admin_id');

        // 超级管理员返回所有权限
        if ($currentAdminId == 1) {
            success($allPermissions);
            return;
        }

        // 普通管理员：只返回自己拥有的权限项
        $myPermissions = getAdminPermissions($currentAdminId);
        $filtered = [];

        foreach ($allPermissions as $module => $permConfig) {
            if (!isset($myPermissions[$module]) || empty($myPermissions[$module])) {
                continue;
            }
            $myActions = $myPermissions[$module];
            $filteredActions = [];
            foreach ($permConfig['actions'] as $action => $label) {
                if (in_array($action, $myActions)) {
                    $filteredActions[$action] = $label;
                }
            }
            if (!empty($filteredActions)) {
                $filtered[$module] = [
                    'name'    => $permConfig['name'],
                    'actions' => $filteredActions,
                ];
                if (isset($permConfig['type'])) {
                    $filtered[$module]['type'] = $permConfig['type'];
                }
            }
        }

        success($filtered);
    }

    /**
     * 校验权限 JSON 是否为当前管理员权限的子集
     * 返回 null 表示通过，返回错误消息字符串表示不通过
     */
    private static function validatePermissionSubset(string $permissionsJson, int $currentAdminId): ?string
    {
        $newPermissions = json_decode($permissionsJson, true);
        if (!is_array($newPermissions)) return null;

        $myPermissions = getAdminPermissions($currentAdminId);
        if ($myPermissions === 'all') return null; // 超管

        foreach ($newPermissions as $module => $actions) {
            if (!isset($myPermissions[$module])) {
                return "无权分配您不具备的模块权限：{$module}";
            }
            foreach ((array)$actions as $action) {
                if (!in_array($action, $myPermissions[$module])) {
                    return "无权分配您不具备的操作权限：{$module}.{$action}";
                }
            }
        }
        return null;
    }
}
