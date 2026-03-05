<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

/**
 * 管理后台自定义视图控制器
 *
 * 允许将某些后台页面完全交给自定义 PHP 视图管理，
 * 而不使用 CRUD 配置驱动模式。这类页面通过 iframe 嵌入后台主框架，
 * 认证依赖浏览器 Cookie（admin_token），无需在 URL 中传递 Token。
 *
 * 视图文件存放目录：app/views/admin/
 * 视图文件命名：{viewName}.php
 */
class AdminViewController
{
    /**
     * 渲染自定义管理视图
     *
     * @param string $viewName 视图名称（对应 app/views/admin/{viewName}.php）
     */
    public static function render(string $viewName)
    {
        // ── 鉴权（Cookie 模式，因为是 iframe 加载的 PHP 页面，无法带 Authorization Header）──
        try {
            AuthMiddleware::checkAdmin();
        } catch (\Exception $e) {
            self::renderError(403, '无权访问，请先登录管理后台');
            return;
        }

        // ── 安全：防止路径穿越，只允许字母、数字、下划线、短横线、斜线 ──
        $safe = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $viewName);
        // 再过滤连续 / 和以 / 开头/结尾的情况
        $safe = trim(preg_replace('#/+#', '/', $safe), '/');

        if ($safe === '' || $safe !== $viewName) {
            self::renderError(400, '视图名称不合法');
            return;
        }

        $viewPath = __DIR__ . '/../../views/admin/' . $safe . '.php';

        if (!file_exists($viewPath)) {
            self::renderError(404, "视图 [{$safe}] 不存在，请先在 app/views/admin/ 目录下创建对应的 PHP 文件。");
            return;
        }

        // ── 视图访问权限校验（超管跳过，普通管理员必须有 view_{name}.access 权限）──
        $adminId = \Flight::get('admin_id');
        if ($adminId != 1) {
            require_once __DIR__ . '/../../helpers/permission.php';
            if (!hasPermission($adminId, 'view_' . $safe, 'access')) {
                self::renderError(403, '您没有访问此页面的权限，请联系超级管理员授权。');
                return;
            }
        }

        // ── 将当前管理员信息注入视图 ──
        $currentAdmin = \Flight::get('currentUser');

        // 执行视图文件
        include $viewPath;
    }

    /**
     * 渲染错误页（内嵌 HTML，适合 iframe 场景）
     */
    private static function renderError(int $httpCode, string $message)
    {
        http_response_code($httpCode);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>错误 {$httpCode}</title>
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=3.3.0">
<style>
body { display:flex; align-items:center; justify-content:center; height:100vh; margin:0; background:#f0f2f7; }
.err-box { text-align:center; padding:40px; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); }
.err-code { font-size:64px; font-weight:700; color:#e74c3c; line-height:1; }
.err-msg  { margin-top:12px; color:#666; font-size:15px; }
</style>
</head>
<body>
  <div class="err-box">
    <div class="err-code">{$httpCode}</div>
    <div class="err-msg">{$message}</div>
  </div>
</body>
</html>
HTML;
        exit;
    }
}
