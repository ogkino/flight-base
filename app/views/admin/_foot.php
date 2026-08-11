<?php
/**
 * 管理后台自定义视图公共底部片段
 *
 * 在自定义视图文件末尾 include 此文件，即可引入：
 *   - Layui JS / jQuery
 *   - config.js（getToken / request / getLocale，自动带 X-Locale）
 *   - i18n.js（t() / applyI18n，与主框架字典一致）
 *   - common.js
 *   - window.VIEW_I18N + vt(key)（本页语言包 view section，供弹层内 JS 文案）
 *
 * 使用方式：
 *   <?php include __DIR__ . '/_foot.php'; ?>
 *   <script>
 *       // 静态 HTML：<?= lang('view.xxx') ?> 或 data-i18n="..."
 *       // 动态 JS：vt('xxx', '兜底')
 *   </script>
 *   </body>
 *   </html>
 */
$viewI18nJson = json_encode(langSection('view'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
    <script src="/admin/assets/js/layui2.8.18.js"></script>
    <script src="/admin/assets/js/jquery-3.6.0.min.js"></script>
    <script src="/admin/assets/js/config.js?v=2.4.1"></script>
    <script src="/admin/assets/js/i18n.js?v=1.1.0"></script>
    <script src="/admin/assets/js/common.js?v=2.4.0"></script>
    <script>
        window.VIEW_I18N = <?= $viewI18nJson ?: '{}' ?>;
        /**
         * View 弹层文案（来自后端 lang 包 view section）
         * @param {string} key
         * @param {string} [fallback]
         */
        function vt(key, fallback) {
            if (window.VIEW_I18N && Object.prototype.hasOwnProperty.call(window.VIEW_I18N, key)
                && window.VIEW_I18N[key] != null && window.VIEW_I18N[key] !== '') {
                return window.VIEW_I18N[key];
            }
            if (typeof t === 'function') {
                var fromFront = t('view.' + key, null);
                if (fromFront != null && fromFront !== '' && fromFront !== ('view.' + key)) {
                    return fromFront;
                }
            }
            return fallback != null ? fallback : key;
        }
        if (typeof applyI18n === 'function') {
            applyI18n(document);
        }
    </script>
