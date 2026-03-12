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

use Medoo\Medoo;
use App\Middleware\CorsMiddleware;

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
    \App\Api\HealthController::check();
});

Flight::route('GET /api/captcha', function () {
    \App\Api\CaptchaController::generate();
});

// ========== 前端业务接口 ==========

Flight::route('POST /api/login', function () {
    \App\Api\AuthController::login();
});

Flight::route('GET /api/info', function () {
    \App\Api\AuthController::info();
});

Flight::route('POST /api/logout', function () {
    \App\Api\AuthController::logout();
});

// ========== 前端页面路由（Views）==========

Flight::route('GET /articles', function () {
    \App\Api\ArticleController::listPage();
});

Flight::route('GET /article/@id', function ($id) {
    \App\Api\ArticleController::detailPage($id);
});

Flight::route('GET /api/articles', function () {
    \App\Api\ArticleController::listApi();
});

Flight::route('GET /api/article/@id', function ($id) {
    \App\Api\ArticleController::detailApi($id);
});

// ========== 管理后台接口（/api/admin/*）==========

Flight::route('POST /api/admin/login', function () {
    \App\Api\Admin\AuthController::login();
});

Flight::route('GET /api/admin/info', function () {
    \App\Api\Admin\AuthController::info();
});

Flight::route('POST /api/admin/logout', function () {
    \App\Api\Admin\AuthController::logout();
});

Flight::route('POST /api/admin/change-password', function () {
    \App\Api\Admin\AuthController::changePassword();
});

Flight::route('GET /api/admin/config', function () {
    \App\Api\Admin\ConfigController::getPageConfig();
});

Flight::route('GET /api/admin/menus', function () {
    \App\Api\Admin\ConfigController::getMenus();
});

Flight::route('GET /api/admin/system', function () {
    \App\Api\Admin\ConfigController::getSystemConfig();
});

// 仪表盘统计接口
Flight::route('GET /api/admin/stats/users', function () {
    \App\Api\Admin\DashboardController::getUsersCount();
});

Flight::route('GET /api/admin/stats/articles', function () {
    \App\Api\Admin\DashboardController::getArticlesCount();
});

Flight::route('GET /api/admin/stats/views', function () {
    \App\Api\Admin\DashboardController::getTodayViews();
});

Flight::route('GET /api/admin/stats/system', function () {
    \App\Api\Admin\DashboardController::getSystemStatus();
});

// 文件上传
Flight::route('POST /api/admin/upload', function () {
    \App\Api\Admin\UploadController::upload();
});

// 用户管理
Flight::route('GET /api/admin/users', function () {
    \App\Api\Admin\UserController::list();
});

Flight::route('POST /api/admin/user', function () {
    \App\Api\Admin\UserController::create();
});

Flight::route('POST /api/admin/user/@id', function ($id) {
    \App\Api\Admin\UserController::update($id);
});

Flight::route('DELETE /api/admin/user/@id', function ($id) {
    \App\Api\Admin\UserController::delete($id);
});

// 文章管理
Flight::route('GET /api/admin/articles', function () {
    \App\Api\Admin\ArticleController::list();
});

Flight::route('GET /api/admin/articles/export', function () {
    \App\Api\Admin\ArticleController::export();
});

Flight::route('POST /api/admin/article', function () {
    \App\Api\Admin\ArticleController::create();
});

Flight::route('POST /api/admin/article/@id', function ($id) {
    \App\Api\Admin\ArticleController::update($id);
});

Flight::route('DELETE /api/admin/article/@id', function ($id) {
    \App\Api\Admin\ArticleController::delete($id);
});

// 管理员管理
Flight::route('GET /api/admin/admins', function () {
    \App\Api\Admin\AdminManageController::list();
});

Flight::route('POST /api/admin/admin', function () {
    \App\Api\Admin\AdminManageController::create();
});

Flight::route('POST /api/admin/admin/@id', function ($id) {
    \App\Api\Admin\AdminManageController::update($id);
});

Flight::route('DELETE /api/admin/admin/@id', function ($id) {
    \App\Api\Admin\AdminManageController::delete($id);
});

Flight::route('GET /api/admin/permissions', function () {
    \App\Api\Admin\AdminManageController::getPermissionOptions();
});

// CRUD 可视化设计器
Flight::route('GET /api/admin/crud-designer/config', function () {
    \App\Api\Admin\CrudDesignerController::getConfig();
});

Flight::route('POST /api/admin/crud-designer/save', function () {
    \App\Api\Admin\CrudDesignerController::saveConfig();
});

// ========== 管理后台自定义视图页面（View 模式）==========
Flight::route('GET /admin/view/@viewName', function ($viewName) {
    \App\Api\Admin\AdminViewController::render($viewName);
});

// ========== 404 处理 ==========
Flight::map('notFound', function () {
    error('接口不存在', 404);
});
