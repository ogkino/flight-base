<?php
/**
 * 管理后台自定义视图公共底部片段
 *
 * 在自定义视图文件末尾 include 此文件，即可引入：
 *   - Layui JS
 *   - jQuery
 *   - config.js（含 getToken / request 等公共方法）
 *   - common.js（含 successMsg / errorMsg / loading 等辅助函数）
 *
 * 注意：
 *   视图页面通过 Cookie 完成 PHP 层鉴权。
 *   如需在视图内发起 AJAX 请求，直接使用 request() 函数即可，
 *   它会自动从 localStorage 读取 Token 并附加到 Authorization Header。
 *
 * 使用方式：
 *   <?php include __DIR__ . '/_foot.php'; ?>
 *   <script>
 *       // 你自己的 JS 逻辑写在这里
 *   </script>
 *   </body>
 *   </html>
 */
?>
    <script src="https://unpkg.com/layui@2.8.18/dist/layui.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/admin/assets/js/config.js?v=2.3.7"></script>
    <script src="/admin/assets/js/common.js?v=2.3.7"></script>
