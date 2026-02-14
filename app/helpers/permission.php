<?php
/**
 * 权限验证辅助函数
 */

/**
 * 检查管理员是否有指定权限
 * 
 * @param int $adminId 管理员ID
 * @param string $module 模块名（如：users, articles）
 * @param string $action 操作名（如：list, create, update, delete）
 * @return bool
 */
function hasPermission($adminId, $module, $action)
{
    $db = db();
    
    // 获取管理员信息
    $admin = $db->get('admin', ['id', 'permissions'], ['id' => $adminId]);
    
    if (!$admin) {
        return false;
    }
    
    // ID=1 的超级管理员拥有所有权限
    if ($admin['id'] == 1) {
        return true;
    }
    
    // 如果 permissions 为空，没有任何权限
    if (empty($admin['permissions'])) {
        return false;
    }
    
    // 解析权限 JSON
    $permissions = json_decode($admin['permissions'], true);
    
    if (!is_array($permissions)) {
        return false;
    }
    
    // 检查是否有该模块的权限
    if (!isset($permissions[$module])) {
        return false;
    }
    
    // 检查是否有该操作的权限
    return in_array($action, $permissions[$module]);
}

/**
 * 获取管理员的所有权限
 * 
 * @param int $adminId 管理员ID
 * @return array
 */
function getAdminPermissions($adminId)
{
    $db = db();
    
    $admin = $db->get('admin', ['id', 'permissions'], ['id' => $adminId]);
    
    if (!$admin) {
        return [];
    }
    
    // ID=1 的超级管理员拥有所有权限
    if ($admin['id'] == 1) {
        return 'all';  // 特殊标记，表示所有权限
    }
    
    if (empty($admin['permissions'])) {
        return [];
    }
    
    $permissions = json_decode($admin['permissions'], true);
    
    return is_array($permissions) ? $permissions : [];
}

/**
 * 获取所有可用的权限配置
 * 
 * @return array
 */
function getAllPermissions()
{
    // 从 CrudConfig 获取所有模块
    require_once __DIR__ . '/../config/CrudConfig.php';
    
    $menus = CrudConfig::getMenus();
    $permissions = [];
    
    foreach ($menus as $groupName => $groupConfig) {
        // 兼容两种格式
        $items = isset($groupConfig['items']) ? $groupConfig['items'] : $groupConfig;
        
        foreach ($items as $item) {
            $page = $item['page'];
            
            // 排除特殊页面
            if (in_array($page, ['dashboard', 'changePassword', 'crud_designer'])) {
                continue;
            }
            
            $permissions[$page] = [
                'name' => $item['name'],
                'actions' => [
                    'list' => '查看列表',
                    'create' => '新增',
                    'update' => '编辑',
                    'delete' => '删除',
                    'export' => '导出',
                    'custom' => '自定义'
                ]
            ];
        }
    }
    
    return $permissions;
}

/**
 * 验证权限（用于控制器）
 * 如果没有权限，自动返回错误并终止
 * 
 * @param string $module 模块名
 * @param string $action 操作名
 */
function checkPermission($module, $action)
{
    $adminId = Flight::get('admin_id');
    
    if (!$adminId) {
        error('请先登录', 401);
        Flight::stop();
        exit;
    }
    
    if (!hasPermission($adminId, $module, $action)) {
        error('无权限访问', 403);
        Flight::stop();
        exit;
    }
}
