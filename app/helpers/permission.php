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
    
    // 用 localizeConfig() 解析 name/name_en 等后缀，保证权限配置面板里的模块名跟随当前语言
    $menus = localizeConfig(CrudConfig::getMenus());
    $permissions = [];
    
    foreach ($menus as $groupName => $groupConfig) {
        // 兼容两种格式
        $items = isset($groupConfig['items']) ? $groupConfig['items'] : $groupConfig;
        
        foreach ($items as $item) {
            // ── 自定义视图类型（type = 'view'）──
            if (isset($item['type']) && $item['type'] === 'view') {
                if (empty($item['view'])) continue;
                $moduleKey = 'view_' . $item['view'];
                $permissions[$moduleKey] = [
                    'name' => $item['name'],
                    'type' => 'view',
                    'actions' => [
                        'access' => lang('permission.access')
                    ]
                ];
                continue;
            }

            // ── 普通 CRUD 页面 ──
            $page = $item['page'] ?? '';
            if (empty($page)) continue;

            // 排除特殊页面
            if (in_array($page, ['dashboard', 'changePassword', 'crud_designer'])) {
                continue;
            }
            
            $permissions[$page] = [
                'name' => $item['name'],
                'actions' => [
                    'list' => lang('permission.list'),
                    'create' => lang('permission.create'),
                    'update' => lang('permission.update'),
                    'delete' => lang('permission.delete'),
                    'export' => lang('permission.export'),
                    'custom' => lang('permission.custom')
                ]
            ];
        }
    }

    // 说明：字典数据（dictData）的管理入口已合并进「字典管理」（dictType）列表的
    // "配置数据"弹层，不再作为独立的权限维度。字典数据的增删改查复用 dictType 的
    // list/create/update/delete 四个动作（见 DictDataController），因此这里不再
    // 单独注册 dictData 权限项。

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
        error(lang('common.please_login'), 401);
        // error() 内部会调用 terminateRequest()，以下代码不会执行
        // 保留 terminateRequest() 作为防御性保证（静态分析友好）
        terminateRequest();
    }

    if (!hasPermission($adminId, $module, $action)) {
        error(lang('common.no_permission'), 403);
        terminateRequest();
    }
}
