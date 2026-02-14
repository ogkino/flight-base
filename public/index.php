<?php
/**
 * Flight Base 框架入口文件
 * 
 * 这个文件只负责：
 * 1. 初始化配置
 * 2. 注册服务
 * 3. 定义路由
 * 4. 应用中间件
 * 
 * 业务逻辑请放在 app/api/ 目录
 * 管理后台业务请放在 app/api/admin/ 目录
 */

// 引入自动加载
require __DIR__ . '/../vendor/autoload.php';

// 加载环境变量
loadEnv(__DIR__ . '/../.env');

// 加载安全函数（如果 composer autoload 未生效，手动加载）
if (!function_exists('cleanInput')) {
    require __DIR__ . '/../app/helpers/security.php';
}

// 加载权限验证函数
if (!function_exists('hasPermission')) {
    require __DIR__ . '/../app/helpers/permission.php';
}

use Medoo\Medoo;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\CaptchaMiddleware;
use App\Api\HealthController;
use App\Api\AuthController;
use App\Api\CaptchaController;
use App\Api\Admin\AuthController as AdminAuthController;
use App\Api\Admin\UserController as AdminUserController;

// ==================== 基础配置 ====================
$appConfig = require __DIR__ . '/../app/config/app.php';

date_default_timezone_set($appConfig['timezone']);

// 配置视图路径
Flight::set('flight.views.path', __DIR__ . '/../app/views');

// 错误报告设置
if ($appConfig['debug']) {
    // 开发环境：显示所有错误
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    Flight::set('flight.log_errors', true);
} else {
    // 生产环境：不显示错误
    error_reporting(0);
    ini_set('display_errors', 0);
    Flight::set('flight.log_errors', true);
    
    // 自定义错误处理（不显示详细信息）
    Flight::map('error', function(Throwable $error){
        // 记录错误日志
        writeLog('Error: ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine(), 'error');
        
        // 返回通用错误信息（不暴露细节）
        Flight::json([
            'code' => 500,
            'msg' => '服务器内部错误',
            'data' => null
        ], 500);
    });
}

// ==================== 注册服务 ====================

// 注册数据库服务
Flight::register('db', Medoo::class, [
    require __DIR__ . '/../app/config/database.php'
]);

// ==================== 全局中间件 ====================

// CORS 跨域中间件
Flight::before('start', function(){
    CorsMiddleware::handle();
});

// 请求日志中间件
Flight::before('start', function(){
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    writeLog("{$method} {$uri} from {$ip}", 'info');
});

// ==================== 路由定义 ====================

// ========== 公共接口（无需登录）==========

// 首页
Flight::route('GET /', function(){
    Flight::render('home/index');
});

// 健康检查
Flight::route('GET /api/health', function(){
    HealthController::check();
});

// 验证码接口
Flight::route('GET /api/captcha', function(){
    CaptchaController::generate();
});

// ========== 前端业务接口 ==========

// 用户登录
Flight::route('POST /api/login', function(){
    AuthController::login();
});

// 获取当前用户信息（需要登录）
Flight::route('GET /api/info', function(){
    AuthMiddleware::check();
    AuthController::info();
});

// 退出登录
Flight::route('POST /api/logout', function(){
    AuthMiddleware::check();
    AuthController::logout();
});

// ========== 前端页面路由（Views）==========

// 文章列表页面
Flight::route('GET /articles', function(){
    \App\Api\ArticleController::listPage();
});

// 文章详情页面
Flight::route('GET /article/@id', function($id){
    \App\Api\ArticleController::detailPage($id);
});

// 文章列表 API（JSON）
Flight::route('GET /api/articles', function(){
    \App\Api\ArticleController::listApi();
});

// 文章详情 API（JSON）
Flight::route('GET /api/article/@id', function($id){
    \App\Api\ArticleController::detailApi($id);
});

// ========== 管理后台接口（/api/admin/*）==========

// 管理员登录
Flight::route('POST /api/admin/login', function(){
    CaptchaMiddleware::handle();
    AdminAuthController::login();
});

// 管理员信息
Flight::route('GET /api/admin/info', function(){
    AdminAuthController::info();
});

// 管理员退出
Flight::route('POST /api/admin/logout', function(){
    AdminAuthController::logout();
});

// 修改密码
Flight::route('POST /api/admin/change-password', function(){
    AdminAuthController::changePassword();
});

// 获取页面配置（配置驱动）
Flight::route('GET /api/admin/config', function(){
    \App\Api\Admin\ConfigController::getPageConfig();
});

// 获取菜单配置
Flight::route('GET /api/admin/menus', function(){
    \App\Api\Admin\ConfigController::getMenus();
});

// 获取系统配置
Flight::route('GET /api/admin/system', function(){
    \App\Api\Admin\ConfigController::getSystemConfig();
});

// 仪表盘统计接口
Flight::route('GET /api/admin/stats/users', function(){
    \App\Api\Admin\DashboardController::getUsersCount();
});

Flight::route('GET /api/admin/stats/articles', function(){
    \App\Api\Admin\DashboardController::getArticlesCount();
});

Flight::route('GET /api/admin/stats/views', function(){
    \App\Api\Admin\DashboardController::getTodayViews();
});

Flight::route('GET /api/admin/stats/system', function(){
    \App\Api\Admin\DashboardController::getSystemStatus();
});

// 文件上传
Flight::route('POST /api/admin/upload', function(){
    \App\Api\Admin\UploadController::upload();
});

// 用户列表
Flight::route('GET /api/admin/users', function(){
    AdminUserController::list();
});

// 创建用户
Flight::route('POST /api/admin/user', function(){
    AdminUserController::create();
});

// 更新用户（改为 POST，和文章管理保持一致）
Flight::route('POST /api/admin/user/@id', function($id){
    AdminUserController::update($id);
});

// 删除用户
Flight::route('DELETE /api/admin/user/@id', function($id){
    AdminUserController::delete($id);
});

// 文章管理
Flight::route('GET /api/admin/articles', function(){
    \App\Api\Admin\ArticleController::list();
});

Flight::route('GET /api/admin/articles/export', function(){
    \App\Api\Admin\ArticleController::export();
});

Flight::route('POST /api/admin/article', function(){
    \App\Api\Admin\ArticleController::create();
});

Flight::route('POST /api/admin/article/@id', function($id){
    \App\Api\Admin\ArticleController::update($id);
});

Flight::route('DELETE /api/admin/article/@id', function($id){
    \App\Api\Admin\ArticleController::delete($id);
});

// 管理员管理
Flight::route('GET /api/admin/admins', function(){
    \App\Api\Admin\AdminManageController::list();
});

Flight::route('POST /api/admin/admin', function(){
    \App\Api\Admin\AdminManageController::create();
});

Flight::route('POST /api/admin/admin/@id', function($id){
    \App\Api\Admin\AdminManageController::update($id);
});

Flight::route('DELETE /api/admin/admin/@id', function($id){
    \App\Api\Admin\AdminManageController::delete($id);
});

// 获取权限选项
Flight::route('GET /api/admin/permissions', function(){
    \App\Api\Admin\AdminManageController::getPermissionOptions();
});

// CRUD 可视化设计器
Flight::route('GET /api/admin/crud-designer/config', function(){
    \App\Api\Admin\CrudDesignerController::getConfig();
});

Flight::route('POST /api/admin/crud-designer/save', function(){
    \App\Api\Admin\CrudDesignerController::saveConfig();
});

// ========== 404 处理 ==========
Flight::map('notFound', function(){
    error('接口不存在', 404);
});

// ==================== 启动应用 ====================
Flight::start();
