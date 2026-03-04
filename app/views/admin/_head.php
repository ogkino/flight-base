<?php
/**
 * 管理后台自定义视图公共头部片段
 *
 * 在自定义视图文件顶部 include 此文件，即可获得：
 *   - 响应式 meta 标签
 *   - Layui CSS
 *   - admin.css 自定义样式
 *   - 可选的 wangEditor 富文本样式
 *
 * 使用方式（在你的视图文件中）：
 *   <?php
 *   $pageTitle = '我的自定义页面';   // 可选，设置页面标题
 *   include __DIR__ . '/_head.php';
 *   ?>
 *   <div class="view-container">
 *       ... 你的内容 ...
 *   </div>
 *   <?php include __DIR__ . '/_foot.php'; ?>
 */
$pageTitle = $pageTitle ?? '管理页面';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.18/dist/css/layui.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=3.3.0">
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
