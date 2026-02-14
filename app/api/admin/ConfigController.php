<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

/**
 * 配置控制器
 * 返回页面配置给前端
 */
class ConfigController
{
    /**
     * 获取页面配置
     */
    public static function getPageConfig()
    {
        AuthMiddleware::checkAdmin();
        
        $page = getQuery('page', 'users');
        
        // 加载配置类
        require_once __DIR__ . '/../../config/CrudConfig.php';
        
        $method = str_replace('-', '', ucwords($page, '-'));
        $method = lcfirst($method);
        
        if (method_exists('CrudConfig', $method)) {
            $config = call_user_func(['CrudConfig', $method]);
            success($config);
        } else {
            error('配置不存在');
        }
    }
    
    /**
     * 获取菜单配置
     * 
     * 从 CrudConfig 自动读取所有菜单配置，并根据权限过滤
     */
    public static function getMenus()
    {
        AuthMiddleware::checkAdmin();
        
        // 加载配置类
        require_once __DIR__ . '/../../config/CrudConfig.php';
        
        $menus = \CrudConfig::getMenus();
        
        // 获取当前管理员 ID
        $adminId = \Flight::get('admin_id');
        
        // 如果是超级管理员（ID=1），返回所有菜单
        if ($adminId == 1) {
            success($menus);
            return;
        }
        
        // 获取管理员权限
        $permissions = getAdminPermissions($adminId);
        
        // 过滤菜单
        $filteredMenus = [];
        
        foreach ($menus as $groupName => $groupConfig) {
            // 兼容两种格式
            $items = isset($groupConfig['items']) ? $groupConfig['items'] : $groupConfig;
            $groupIcon = isset($groupConfig['icon']) ? $groupConfig['icon'] : null;
            
            $filteredItems = [];
            
            foreach ($items as $item) {
                $page = $item['page'];
                
                // CRUD 设计器仅超级管理员可见（已在外层判断，这里为安全起见再次排除）
                if ($page === 'crud_designer') {
                    continue;
                }
                
                // 特殊页面（仪表盘、修改密码）始终显示
                if (in_array($page, ['dashboard', 'changePassword'])) {
                    $filteredItems[] = $item;
                    continue;
                }
                
                // 检查是否有该模块的任意权限（list、create、update、delete 中任意一个）
                if (isset($permissions[$page]) && !empty($permissions[$page])) {
                    $filteredItems[] = $item;
                }
            }
            
            // 如果该分组还有菜单项，添加到结果中
            if (!empty($filteredItems)) {
                if ($groupIcon) {
                    // 新格式
                    $filteredMenus[$groupName] = [
                        'icon' => $groupIcon,
                        'items' => $filteredItems
                    ];
                } else {
                    // 旧格式
                    $filteredMenus[$groupName] = $filteredItems;
                }
            }
        }
        
        success($filteredMenus);
    }
    
    /**
     * 获取系统配置
     * 
     * 返回系统名称、版本等信息
     */
    public static function getSystemConfig()
    {
        AuthMiddleware::checkAdmin();
        
        // 直接重新加载配置，避免缓存问题
        $config = require __DIR__ . '/../../config/app.php';
        
        // 调试信息（可以在日志中查看）
        writeLog('获取系统配置，admin_name: ' . ($config['admin_name'] ?? 'undefined'), 'info');
        
        success([
            'name' => $config['name'] ?? 'Flight Base',
            'admin_name' => $config['admin_name'] ?? 'Flight Base 管理后台',
            'version' => $config['version'] ?? '1.0.0'
        ]);
    }
}
