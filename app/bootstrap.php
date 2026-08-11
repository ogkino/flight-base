<?php
/**
 * Flight Base 框架启动文件
 *
 * 包含所有一次性初始化逻辑：配置、服务注册、中间件、路由定义。
 * 被以下入口文件共同引用：
 *   - public/index.php   → PHP-FPM / FrankenPHP Classic 模式
 *   - public/worker.php  → FrankenPHP Worker 模式
 *
 * 本文件不调用 Flight::start()，由各入口文件自行决定何时启动。
 */

// 引入自动加载
require __DIR__ . '/../vendor/autoload.php';

// 加载环境变量
loadEnv(__DIR__ . '/../.env');

// 加载安全函数（如果 composer autoload 未生效，手动加载）
if (!function_exists('cleanInput')) {
    require __DIR__ . '/helpers/security.php';
}

// 加载权限验证函数
if (!function_exists('hasPermission')) {
    require __DIR__ . '/helpers/permission.php';
}

// 加载多语言（i18n）函数
if (!function_exists('lang')) {
    require __DIR__ . '/helpers/i18n.php';
}

use Medoo\Medoo;
use App\middleware\CorsMiddleware;

// ==================== 基础配置 ====================
$appConfig = require __DIR__ . '/config/app.php';

date_default_timezone_set($appConfig['timezone']);

// 配置视图路径
Flight::set('flight.views.path', __DIR__ . '/views');

// 错误报告设置
if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    Flight::set('flight.log_errors', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    Flight::set('flight.log_errors', true);

    Flight::map('error', function (Throwable $error) {
        writeLog('Error: ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine(), 'error');
        Flight::json([
            'code' => 500,
            'msg'  => '服务器内部错误',
            'data' => null
        ], 500);
    });
}

// ==================== 注册服务 ====================

Flight::register('db', Medoo::class, [
    require __DIR__ . '/config/database.php'
]);

// ==================== 全局中间件 ====================

Flight::before('start', function () {
    CorsMiddleware::handle();
});

Flight::before('start', function () {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri    = $_SERVER['REQUEST_URI'];
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    writeLog("{$method} {$uri} from {$ip}", 'info');
});

// ==================== 路由定义 ====================

// ========== 公共接口（无需登录）==========

Flight::route('GET /', function () {
    Flight::render('home/index');
});

Flight::route('GET /api/health', function () {
    \App\api\HealthController::check();
});

Flight::route('GET /api/captcha', function () {
    \App\api\CaptchaController::generate();
});

// ========== 前端业务接口 ==========

Flight::route('POST /api/login', function () {
    \App\api\AuthController::login();
});

Flight::route('GET /api/info', function () {
    \App\api\AuthController::info();
});

Flight::route('POST /api/logout', function () {
    \App\api\AuthController::logout();
});

// ========== 前端页面路由（Views）==========

Flight::route('GET /articles', function () {
    \App\api\ArticleController::listPage();
});

Flight::route('GET /article/@id', function ($id) {
    \App\api\ArticleController::detailPage($id);
});

Flight::route('GET /api/articles', function () {
    \App\api\ArticleController::listApi();
});

Flight::route('GET /api/article/@id', function ($id) {
    \App\api\ArticleController::detailApi($id);
});

// ========== 管理后台接口（/api/admin/*）==========

// 主题配置（无鉴权；勿用 .js 后缀——Linux Nginx 常把 *.js 当静态文件直接 404）
Flight::route('GET /api/admin/theme-config', function () {
    \App\config\AdminThemeConfig::emitThemeConfigJs();
});
// 兼容旧路径（Apache/Windows 或已配置把 /api 交给 PHP 的环境）
Flight::route('GET /api/admin/theme-config.js', function () {
    \App\config\AdminThemeConfig::emitThemeConfigJs();
});

// 登录页公开配置（验证码开关等，无鉴权）
Flight::route('GET /api/admin/login-config', function () {
    success([
        'captcha_enabled' => \App\api\CaptchaController::isEnabled(),
    ]);
});

Flight::route('POST /api/admin/login', function () {
    \App\api\admin\AuthController::login();
});

Flight::route('GET /api/admin/info', function () {
    \App\api\admin\AuthController::info();
});

Flight::route('POST /api/admin/logout', function () {
    \App\api\admin\AuthController::logout();
});

Flight::route('POST /api/admin/change-password', function () {
    \App\api\admin\AuthController::changePassword();
});

Flight::route('GET /api/admin/config', function () {
    \App\api\admin\ConfigController::getPageConfig();
});

Flight::route('GET /api/admin/menus', function () {
    \App\api\admin\ConfigController::getMenus();
});

Flight::route('GET /api/admin/system', function () {
    \App\api\admin\ConfigController::getSystemConfig();
});

// 仪表盘统计接口
Flight::route('GET /api/admin/stats/users', function () {
    \App\api\admin\DashboardController::getUsersCount();
});

Flight::route('GET /api/admin/stats/articles', function () {
    \App\api\admin\DashboardController::getArticlesCount();
});

Flight::route('GET /api/admin/stats/views', function () {
    \App\api\admin\DashboardController::getTodayViews();
});

Flight::route('GET /api/admin/stats/system', function () {
    \App\api\admin\DashboardController::getSystemStatus();
});

// 文件上传
Flight::route('POST /api/admin/upload', function () {
    \App\api\admin\UploadController::upload();
});

// 用户管理
Flight::route('GET /api/admin/users', function () {
    \App\api\admin\UserController::list();
});

Flight::route('POST /api/admin/user', function () {
    \App\api\admin\UserController::create();
});

Flight::route('POST /api/admin/user/@id', function ($id) {
    \App\api\admin\UserController::update($id);
});

Flight::route('DELETE /api/admin/user/@id', function ($id) {
    \App\api\admin\UserController::delete($id);
});

// 文章管理
Flight::route('GET /api/admin/articles', function () {
    \App\api\admin\ArticleController::list();
});

Flight::route('GET /api/admin/articles/export', function () {
    \App\api\admin\ArticleController::export();
});

Flight::route('POST /api/admin/article', function () {
    \App\api\admin\ArticleController::create();
});

Flight::route('POST /api/admin/article/@id', function ($id) {
    \App\api\admin\ArticleController::update($id);
});

Flight::route('DELETE /api/admin/article/@id', function ($id) {
    \App\api\admin\ArticleController::delete($id);
});

// 管理员管理
Flight::route('GET /api/admin/admins', function () {
    \App\api\admin\AdminManageController::list();
});

Flight::route('POST /api/admin/admin', function () {
    \App\api\admin\AdminManageController::create();
});

Flight::route('POST /api/admin/admin/@id', function ($id) {
    \App\api\admin\AdminManageController::update($id);
});

Flight::route('DELETE /api/admin/admin/@id', function ($id) {
    \App\api\admin\AdminManageController::delete($id);
});

Flight::route('GET /api/admin/permissions', function () {
    \App\api\admin\AdminManageController::getPermissionOptions();
});

// 字典类型管理
Flight::route('GET /api/admin/dict/types', function () {
    \App\api\admin\DictTypeController::list();
});

Flight::route('GET /api/admin/dict/types/options', function () {
    \App\api\admin\DictTypeController::options();
});

Flight::route('POST /api/admin/dict/type', function () {
    \App\api\admin\DictTypeController::create();
});

Flight::route('POST /api/admin/dict/type/@id', function ($id) {
    \App\api\admin\DictTypeController::update($id);
});

Flight::route('DELETE /api/admin/dict/type/@id', function ($id) {
    \App\api\admin\DictTypeController::delete($id);
});

// 字典数据管理
Flight::route('GET /api/admin/dict/data', function () {
    \App\api\admin\DictDataController::list();
});

Flight::route('GET /api/admin/dict/data/options', function () {
    \App\api\admin\DictDataController::options();
});

Flight::route('POST /api/admin/dict/data', function () {
    \App\api\admin\DictDataController::create();
});

Flight::route('POST /api/admin/dict/data/@id', function ($id) {
    \App\api\admin\DictDataController::update($id);
});

Flight::route('DELETE /api/admin/dict/data/@id', function ($id) {
    \App\api\admin\DictDataController::delete($id);
});

// 字典数据管理弹层页面（由「字典管理」列表的"配置数据"行操作以 iframe 方式打开）
// 权限跟随 dictType/dictData 权限，不走 /admin/view/{viewName} 的独立视图权限体系
Flight::route('GET /admin/dict-data-manage', function () {
    \App\api\admin\DictDataController::managePage();
});

// CRUD 可视化设计器
Flight::route('GET /api/admin/crud-designer/config', function () {
    \App\api\admin\CrudDesignerController::getConfig();
});

Flight::route('POST /api/admin/crud-designer/save', function () {
    \App\api\admin\CrudDesignerController::saveConfig();
});

// ========== 管理后台自定义视图页面（View 模式）==========
Flight::route('GET /admin/view/@viewName', function ($viewName) {
    \App\api\admin\AdminViewController::render($viewName);
});

// ========== 404 处理 ==========
Flight::map('notFound', function () {
    error('接口不存在', 404);
});

