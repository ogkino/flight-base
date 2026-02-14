<?php
namespace App\Api\Admin;

use Flight;
use App\Middleware\AuthMiddleware;

/**
 * 用户管理控制器（管理员）
 */
class UserController
{
    /**
     * 获取用户列表（管理员权限）
     */
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('users', 'list');
        
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
        
        // 总数
        $count = $db->count('users', $where);
        
        // 列表
        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $where['ORDER'] = ['id' => 'DESC'];
        
        $users = $db->select('users', [
            'id',
            'username',
            'nickname',
            'email',
            'phone',
            'status',
            'created_at',
            'last_login_time',
            'expired_at'
        ], $where);
        
        // 使用 Layui 表格专用格式
        layuiTable($users, $count);
    }
    
    /**
     * 创建用户
     */
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('users', 'create');
        
        $db = db();
        
        $username = getPost('username');
        $password = getPost('password', '123456');
        
        if (!$username) {
            error('用户名不能为空');
            return;
        }
        
        // 检查用户名是否存在
        if ($db->has('users', ['username' => $username])) {
            error('用户名已存在');
            return;
        }
        
        $data = [
            'username' => $username,
            'password' => hashPassword($password),
            'nickname' => getPost('nickname', $username),
            'email' => getPost('email'),
            'phone' => getPost('phone'),
            'status' => getPost('status', 1),
            'created_at' => date('Y-m-d H:i:s'),
            'expired_at' => getPost('expired_at'),
        ];
        
        $result = $db->insert('users', $data);
        
        if ($result->rowCount() > 0) {
            writeLog("管理员创建用户: {$username}", 'info');
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
    
    /**
     * 更新用户
     */
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('users', 'update');
        
        $db = db();
        
        // 允许更新的字段
        $fields = ['nickname', 'email', 'phone', 'status', 'expired_at'];
        $data = [];
        
        foreach ($fields as $field) {
            $value = getPost($field);
            // 只有当前端传递了该字段时才更新（支持部分更新）
            if ($value !== null) {
                $data[$field] = $value;
            }
        }
        
        // 如果修改了密码
        if ($password = getPost('password')) {
            $data['password'] = hashPassword($password);
        }
        
        if (empty($data)) {
            error('没有收到更新数据');
            return;
        }
        
        // 总是更新 updated_at
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $result = $db->update('users', $data, ['id' => $id]);
        
        if ($result->rowCount() > 0) {
            writeLog("管理员更新用户 ID: {$id}", 'info');
            success([], '更新成功');
        } else {
            // Medoo update 返回 0 可能是因为数据没变化，也算成功
            success([], '更新成功（无变化）');
        }
    }
    
    /**
     * 删除用户
     */
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('users', 'delete');
        
        $db = db();
        $result = $db->delete('users', ['id' => $id]);
        
        if ($result->rowCount() > 0) {
            writeLog("管理员删除用户 ID: {$id}", 'info');
            success([], '删除成功');
        } else {
            error('删除失败');
        }
    }
}
