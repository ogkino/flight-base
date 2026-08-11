<?php
/**
 * 管理后台自定义视图公共头部片段
 *
 * 在自定义视图文件顶部 include 此文件，即可获得：
 *   - 响应式 meta 标签
 *   - Layui CSS
 *   - admin.css 自定义样式
 *   - 可选主题覆盖 CSS（Cookie admin_theme，与 login_xxx / theme.js 约定一致）
 *   - 当前语言（Cookie admin_locale / ?lang=，与主框架一致）
 *
 * 使用方式（在你的视图文件中）：
 *   <?php
 *   $pageTitle = lang('view.xxx_title');
 *   include __DIR__ . '/_head.php';
 *   ?>
 *   <div class="view-container">
 *       ... 你的内容（静态文案用 lang() / data-i18n） ...
 *   </div>
 *   <?php include __DIR__ . '/_foot.php'; ?>
 */
$pageTitle = $pageTitle ?? lang('view.page_default_title');
$htmlLang = currentLocale() === 'en-US' ? 'en' : 'zh-CN';

$cookieTheme = $_COOKIE['admin_theme'] ?? null;
if (is_string($cookieTheme) && $cookieTheme !== '') {
    $adminTheme = \App\config\AdminThemeConfig::normalizeTheme($cookieTheme);
} else {
    $adminTheme = \App\config\AdminThemeConfig::resolvedDefaultTheme();
}
$menuLayout = \App\config\AdminThemeConfig::menuLayout($adminTheme);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') ?>" data-admin-theme="<?= htmlspecialchars($adminTheme, ENT_QUOTES, 'UTF-8') ?>" data-menu-layout="<?= htmlspecialchars($menuLayout, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/admin/assets/css/layui2.8.18.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=3.4.9">
<?php if ($adminTheme !== 'default'): ?>
    <link rel="stylesheet" id="admin-theme-css" href="/admin/assets/css/admin.theme.<?= htmlspecialchars($adminTheme, ENT_QUOTES, 'UTF-8') ?>.css?v=1.4.0">
<?php endif; ?>
    <link rel="stylesheet" id="admin-menu-layout-css" href="/admin/assets/css/admin.menu-layouts.css?v=1.1.0">
    <style>
        /* 视图模式布局：页面铺满整个 iframe，无需侧边栏和顶部导航 */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background: #f0f2f7;
            overflow-x: hidden;
        }
        .view-container {
            padding: 20px;
            min-height: 100%;
            box-sizing: border-box;
        }
        /* 顶部操作栏（可选使用） */
        .view-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .view-toolbar .view-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a2035;
        }
    </style>
</head>
<body>
