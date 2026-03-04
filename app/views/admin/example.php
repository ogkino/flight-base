<?php
/**
 * 示例自定义管理视图
 *
 * 访问路径：GET /admin/view/example
 * 对应菜单配置（CrudConfig.php 中）：
 *   ['name' => '示例视图', 'type' => 'view', 'view' => 'example', 'icon' => 'layui-icon-template']
 *
 * 说明：
 *   此文件由 AdminViewController 以 include 方式加载，
 *   因此可以直接使用以下变量：
 *     $currentAdmin  当前登录的管理员信息（数组）
 *
 * 自定义开发建议：
 *   1. 将此文件复制并重命名，如 system-settings.php
 *   2. 在 CrudConfig.php 的菜单配置中添加对应的 view 类型项
 *   3. 根据业务需求自由编写 PHP / HTML / CSS / JS
 */

$pageTitle = '示例自定义页面';
include __DIR__ . '/_head.php';
?>

<div class="view-container">

    <!-- 顶部操作栏 -->
    <div class="view-toolbar">
        <div class="view-title">
            <i class="layui-icon layui-icon-template" style="margin-right:6px;"></i>
            示例自定义管理页面
        </div>
        <div>
            <button class="layui-btn layui-btn-sm layui-btn-normal" onclick="doSomething()">
                <i class="layui-icon layui-icon-add-1"></i> 执行操作
            </button>
        </div>
    </div>

    <!-- 当前管理员信息卡片 -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-title">
            <i class="layui-icon layui-icon-user"></i> 当前管理员
        </div>
        <div class="layui-form-item" style="margin-top: 16px;">
            <label class="layui-form-label" style="width: 80px;">用户名</label>
            <div class="layui-input-block" style="margin-left: 100px;">
                <p class="layui-form-label" style="width: auto; text-align:left; color: #333;">
                    <?= htmlspecialchars($currentAdmin['username'] ?? '未知') ?>
                </p>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label" style="width: 80px;">角色</label>
            <div class="layui-input-block" style="margin-left: 100px;">
                <p class="layui-form-label" style="width: auto; text-align:left; color: #333;">
                    <?= ($currentAdmin['id'] ?? 0) == 1 ? '超级管理员' : '普通管理员' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- 自定义内容区域 -->
    <div class="card">
        <div class="card-title">
            <i class="layui-icon layui-icon-tips"></i> View 模式说明
        </div>
        <div style="padding: 10px 0; line-height: 2; color: #555;">
            <p>这是一个 <strong>View 模式</strong> 的自定义管理页面示例。</p>
            <br>
            <p><strong>✨ View 模式特点：</strong></p>
            <ul style="padding-left: 20px;">
                <li>✅ 完全脱离 CRUD 配置驱动，自由度极高</li>
                <li>✅ 通过 Cookie 鉴权，无需在 URL 中传递 Token</li>
                <li>✅ 自动引入 admin.css，与后台主题保持一致</li>
                <li>✅ 可直接使用 Layui、jQuery、<code>request()</code> 等公共方法</li>
                <li>✅ <code>$currentAdmin</code> 变量直接可用，包含当前管理员信息</li>
            </ul>
            <br>
            <p><strong>📁 新建 View 步骤：</strong></p>
            <ol style="padding-left: 20px;">
                <li>在 <code>app/views/admin/</code> 目录下创建 <code>my-page.php</code></li>
                <li>在 CRUD 设计器的菜单配置中，添加类型为 <strong>自定义视图</strong> 的菜单项</li>
                <li>填写视图名称 <code>my-page</code>，即可通过 <code>/admin/view/my-page</code> 访问</li>
            </ol>
        </div>
    </div>

    <!-- API 调用示例 -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-title">
            <i class="layui-icon layui-icon-code"></i> API 调用示例
        </div>
        <div id="api-result" style="margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 8px; font-family: monospace; color: #555;">
            点击下方按钮，通过 AJAX 调用后台接口...
        </div>
        <div style="margin-top: 12px;">
            <button class="layui-btn layui-btn-sm" onclick="testApi()">
                <i class="layui-icon layui-icon-refresh"></i> 调用 /api/admin/info
            </button>
        </div>
    </div>

</div>

<?php include __DIR__ . '/_foot.php'; ?>

<script>
    layui.use(['layer'], function() {
        window.layer = layui.layer;
    });

    function doSomething() {
        layer.msg('自定义操作触发！在这里实现你的业务逻辑。', { icon: 1 });
    }

    function testApi() {
        const el = document.getElementById('api-result');
        el.textContent = '请求中...';

        // request() 来自 config.js，自动带 Token
        request('/api/admin/info', { method: 'GET' }).then(res => {
            el.textContent = JSON.stringify(res, null, 2);
        }).catch(err => {
            el.textContent = '请求失败：' + err.message;
        });
    }
</script>

</body>
</html>
