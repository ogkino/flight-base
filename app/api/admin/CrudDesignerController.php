<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;
use ReflectionClass;
use ReflectionMethod;

/**
 * CRUD 可视化设计器控制器
 */
class CrudDesignerController
{
    /**
     * 获取当前所有配置
     */
    public static function getConfig()
    {
        AuthMiddleware::checkAdmin();
        
        // 仅超级管理员可用
        $adminId = \Flight::get('admin_id');
        if ($adminId != 1) {
             error('无权访问', 403);
             return;
        }

        try {
            require_once __DIR__ . '/../../config/CrudConfig.php';
            
            $config = [];
            $class = new ReflectionClass('CrudConfig');
            
            // 获取菜单
            if ($class->hasMethod('getMenus')) {
                $config['menus'] = \CrudConfig::getMenus();
            }
            
            // 获取所有页面配置方法
            $methods = $class->getMethods(ReflectionMethod::IS_STATIC);
            $config['pages'] = [];
            
            foreach ($methods as $method) {
                $name = $method->name;
                // 跳过 getMenus 和私有方法（如果有）
                if ($name === 'getMenus' || $name === 'load' || strpos($name, '_') === 0) {
                    continue;
                }
                
                $config['pages'][$name] = $method->invoke(null);
            }
            
            success($config);
        } catch (\Throwable $e) {
            error('加载配置失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 保存配置
     */
    public static function saveConfig()
    {
        AuthMiddleware::checkAdmin();
        
        // 仅超级管理员可用
        $adminId = \Flight::get('admin_id');
        if ($adminId != 1) {
            error('无权访问', 403);
            return;
        }
        
        // 获取原始 JSON 字符串，使用 JSON_OBJECT_AS_ARRAY 保留顺序
        $jsonStr = \Flight::request()->getBody();
        $data = json_decode($jsonStr, true);
        
        if (empty($data)) {
            error('数据为空');
            return;
        }
        
        $pages = $data['pages'] ?? [];
        
        // 优先使用 menusArray 来保证顺序（解决 JS 对象数字键排序问题）
        $menus = [];
        if (isset($data['menusArray']) && is_array($data['menusArray'])) {
            foreach ($data['menusArray'] as $menuGroup) {
                if (isset($menuGroup['name']) && isset($menuGroup['config'])) {
                    $menus[$menuGroup['name']] = $menuGroup['config'];
                }
            }
        } else {
            $menus = $data['menus'] ?? [];
        }
        
        // 调试日志：记录接收到的菜单顺序
        $menuOrder = array_keys($menus);
        error_log("保存菜单顺序: " . implode(', ', $menuOrder));
        
        // 生成 PHP 代码
        $code = "<?php\n";
        $code .= "/**\n";
        $code .= " * CRUD 配置驱动生成器\n";
        $code .= " * \n";
        $code .= " * 由 CRUD 可视化设计器自动生成\n";
        $code .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $code .= " */\n";
        $code .= "class CrudConfig\n";
        $code .= "{\n";
        
        // 生成 getMenus 方法
        $code .= "    /**\n";
        $code .= "     * 获取所有菜单配置\n";
        $code .= "     */\n";
        $code .= "    public static function getMenus()\n";
        $code .= "    {\n";
        $code .= "        return " . self::exportArray($menus, 2) . ";\n";
        $code .= "    }\n\n";
        
        // 生成各页面方法
        foreach ($pages as $name => $pageConfig) {
            $code .= "    /**\n";
            $code .= "     * " . ($pageConfig['page']['title'] ?? $name) . "配置\n";
            $code .= "     */\n";
            $code .= "    public static function {$name}()\n";
            $code .= "    {\n";
            $code .= "        return " . self::exportArray($pageConfig, 2) . ";\n";
            $code .= "    }\n\n";
        }
        
        $code .= "}\n";
        
        // 写入文件
        $file = __DIR__ . '/../../config/CrudConfig.php';
        if (file_put_contents($file, $code)) {
            success([], '保存成功');
        } else {
            error('保存失败，请检查文件权限');
        }
    }
    
    /**
     * 格式化数组为 PHP 代码（短数组语法）
     * 保留数组顺序，包括关联数组的键顺序
     */
    private static function exportArray($array, $indent = 1)
    {
        if (!is_array($array)) {
            return var_export($array, true);
        }
        
        // 空数组
        if (empty($array)) {
            return "[]";
        }
        
        $indentStr = str_repeat('    ', $indent);
        $subIndentStr = str_repeat('    ', $indent + 1);
        
        $code = "[\n";
        
        // 判断是否为索引数组（连续的数字索引，从0开始）
        $keys = array_keys($array);
        $isIndexed = ($keys === range(0, count($array) - 1));
        
        // 按照原始顺序遍历（foreach 在 PHP 7+ 中保证按插入顺序）
        foreach ($array as $key => $value) {
            $code .= $subIndentStr;
            
            // 索引数组不输出键
            if (!$isIndexed) {
                // 字符串键加引号，数字键不加
                if (is_string($key)) {
                    $code .= "'" . addslashes($key) . "' => ";
                } else {
                    $code .= $key . " => ";
                }
            }
            
            // 递归处理值
            if (is_array($value)) {
                $code .= self::exportArray($value, $indent + 1);
            } else if (is_string($value)) {
                $code .= "'" . addslashes($value) . "'";
            } else if (is_bool($value)) {
                $code .= $value ? 'true' : 'false';
            } else if (is_null($value)) {
                $code .= 'null';
            } else {
                $code .= $value;
            }
            
            $code .= ",\n";
        }
        
        $code .= $indentStr . "]";
        return $code;
    }
}
