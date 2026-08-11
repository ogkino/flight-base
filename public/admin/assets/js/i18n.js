/**
 * 前端多语言字典与翻译工具
 *
 * 依赖 config.js 提供的 getLocale() / setLocale()（必须先于本文件加载）
 *
 * 使用方式：
 *   1. 静态文案：给元素加 data-i18n="section.key"（文本）/ data-i18n-placeholder="..." / data-i18n-title="..."，
 *      页面加载时 applyI18n() 会自动写入翻译后的文案。
 *   2. 动态拼接：JS 代码里用 t('section.key', '兜底文案') 获取当前语言的文案。
 *
 * 字典 key 命名规则、如何新增语言，见 docs/I18N.md
 */

const I18N_DICT = {
    'zh-CN': {
        common: {
            title: '后台管理 - Flight Base',
            login_title: '管理员登录',
            welcome_desc: '请使用管理员账号登录',
            username_placeholder: '请输入用户名',
            password_placeholder: '请输入密码',
            captcha_placeholder: '请输入验证码',
            login_btn: '立即登录',
            login_footer: 'Powered by Flight Base',
            login_default_error: '账号或密码错误',
            logout: '退出',
            logout_confirm: '确定要退出登录吗？',
            confirm_title: '提示',
            admin_default_name: '管理员',
            load_failed: '加载失败',
            login_success: '登录成功',
            copy_success: '复制成功',
            status_normal: '正常',
            status_disabled: '禁用',
            sidebar_default_title: 'Flight Base',
            welcome_card_title: '欢迎使用',
            welcome_card_desc: '这是一个基于 <strong>Flight + Medoo + Layui</strong> 的轻量级后台管理系统框架。',
            welcome_feature_title: '✨ 核心特性（配置驱动）：',
            welcome_feature_1: '✅ <strong>零手写代码</strong>：后端配置 → 前端自动渲染',
            welcome_feature_2: '✅ <strong>AI 极度友好</strong>：配置即代码，AI 轻松生成',
            welcome_feature_3: '✅ <strong>超级轻量</strong>：核心代码不到 500 行',
            welcome_feature_4: '✅ <strong>无需打包</strong>：修改即生效，开发体验极致',
            stat_total_users: '用户总数',
            stat_today_visits: '今日访问',
            stat_total_data: '数据总量',
            stat_system_status: '系统状态',
            stat_system_running: '运行中',
            load_config_failed: '加载配置失败：',
            language: '语言'
        },
        crud: {
            search: '搜索',
            reset: '重置',
            add: '新增',
            edit: '编辑',
            delete: '删除',
            submit: '提交',
            actions: '操作',
            more_actions: '更多操作',
            preview: '预览',
            view: '查看',
            please_select: '请选择',
            please_select_date: '请选择日期',
            please_select_icon: '请选择图标',
            search_icon: '搜索图标...',
            input_then_enter: '输入后回车',
            loading: '加载中...',
            select_icon_title: '选择图标',
            view_file: '查看文件',
            upload_success: '上传成功',
            upload_failed: '上传失败',
            image_upload_failed: '图片上传失败：',
            editor_placeholder: '请输入内容...',
            confirm_delete: '确定删除吗？',
            delete_success: '删除成功',
            status_update_success: '状态更新成功',
            update_failed: '更新失败',
            password_too_short: '密码长度不能少于6位',
            password_mismatch: '两次输入的密码不一致'
        }
    },
    'en-US': {
        common: {
            title: 'Admin Panel - Flight Base',
            login_title: 'Administrator Login',
            welcome_desc: 'Please sign in with your administrator account',
            username_placeholder: 'Enter your username',
            password_placeholder: 'Enter your password',
            captcha_placeholder: 'Enter the captcha code',
            login_btn: 'Sign In',
            login_footer: 'Powered by Flight Base',
            login_default_error: 'Incorrect username or password',
            logout: 'Logout',
            logout_confirm: 'Are you sure you want to log out?',
            confirm_title: 'Notice',
            admin_default_name: 'Administrator',
            load_failed: 'Failed to load',
            login_success: 'Login successful',
            copy_success: 'Copied successfully',
            status_normal: 'Enabled',
            status_disabled: 'Disabled',
            sidebar_default_title: 'Flight Base',
            welcome_card_title: 'Welcome',
            welcome_card_desc: 'A lightweight admin framework built with <strong>Flight + Medoo + Layui</strong>.',
            welcome_feature_title: '✨ Key features (config-driven):',
            welcome_feature_1: '✅ <strong>Zero front-end code</strong>: backend config → auto-rendered UI',
            welcome_feature_2: '✅ <strong>AI friendly</strong>: config is code, easy for AI to generate',
            welcome_feature_3: '✅ <strong>Ultra lightweight</strong>: core code under 500 lines',
            welcome_feature_4: '✅ <strong>No build step</strong>: edits take effect instantly',
            stat_total_users: 'Total Users',
            stat_today_visits: 'Visits Today',
            stat_total_data: 'Total Records',
            stat_system_status: 'System Status',
            stat_system_running: 'Running',
            load_config_failed: 'Failed to load configuration: ',
            language: 'Language'
        },
        crud: {
            search: 'Search',
            reset: 'Reset',
            add: 'Add',
            edit: 'Edit',
            delete: 'Delete',
            submit: 'Submit',
            actions: 'Actions',
            more_actions: 'More actions',
            preview: 'Preview',
            view: 'View',
            please_select: 'Please select',
            please_select_date: 'Please select a date',
            please_select_icon: 'Please select an icon',
            search_icon: 'Search icon...',
            input_then_enter: 'Type and press Enter',
            loading: 'Loading...',
            select_icon_title: 'Select icon',
            view_file: 'View file',
            upload_success: 'Upload successful',
            upload_failed: 'Upload failed',
            image_upload_failed: 'Image upload failed: ',
            editor_placeholder: 'Please enter content...',
            confirm_delete: 'Are you sure you want to delete this?',
            delete_success: 'Deleted successfully',
            status_update_success: 'Status updated successfully',
            update_failed: 'Update failed',
            password_too_short: 'Password must be at least 6 characters',
            password_mismatch: 'Passwords do not match'
        }
    }
};

/**
 * 按 "section.key" 路径读取字典值
 */
function i18nDictGet(dict, key) {
    return key.split('.').reduce((obj, k) => (obj && obj[k] !== undefined) ? obj[k] : undefined, dict);
}

/**
 * 翻译函数
 * @param {string} key      字典 key，如 'crud.search'
 * @param {string} fallback 找不到翻译时的兜底文案（找不到且未传时返回 key 本身）
 */
function t(key, fallback) {
    const locale = (typeof getLocale === 'function') ? getLocale() : 'zh-CN';
    const dict = I18N_DICT[locale] || I18N_DICT['zh-CN'];

    let value = i18nDictGet(dict, key);
    if (value !== undefined) return value;

    // 回退到中文基准字典
    value = i18nDictGet(I18N_DICT['zh-CN'], key);
    if (value !== undefined) return value;

    return fallback !== undefined ? fallback : key;
}

/**
 * 将当前语言应用到页面中所有带 data-i18n* 属性的元素
 * @param {Document|Element} root 应用范围，默认整个文档
 */
function applyI18n(root) {
    root = root || document;

    // 使用 innerHTML 而非 textContent，允许字典文案里包含简单的行内标签（如 <strong>）
    // 用于保留原有的强调样式。字典内容由开发者维护，不涉及用户输入，无 XSS 风险。
    root.querySelectorAll('[data-i18n]').forEach(el => {
        el.innerHTML = t(el.getAttribute('data-i18n'));
    });
    root.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        el.setAttribute('placeholder', t(el.getAttribute('data-i18n-placeholder')));
    });
    root.querySelectorAll('[data-i18n-title]').forEach(el => {
        el.setAttribute('title', t(el.getAttribute('data-i18n-title')));
    });
    root.querySelectorAll('[data-i18n-value]').forEach(el => {
        el.setAttribute('value', t(el.getAttribute('data-i18n-value')));
    });

    document.documentElement.setAttribute('lang', getLocale());
}

/**
 * 切换语言并刷新页面
 * 刷新是最简单可靠的方式：静态文案（data-i18n）、后端配置驱动的文案（菜单/表单/统计等，
 * 通过 X-Locale 请求头返回）都会在页面重新加载时以新语言呈现
 */
function switchLocale(locale) {
    if (getLocale() === locale) return;
    setLocale(locale);
    location.reload();
}

/**
 * 渲染语言切换下拉框
 * @param {string} containerId 承载下拉框的容器元素 id
 */
function renderLocaleSwitcher(containerId) {
    const el = document.getElementById(containerId);
    if (!el) return;

    const current = getLocale();
    el.innerHTML =
        '<select id="localeSwitcher" class="locale-switcher" title="' + t('common.language', 'Language') + '">' +
            '<option value="zh-CN"' + (current === 'zh-CN' ? ' selected' : '') + '>中文</option>' +
            '<option value="en-US"' + (current === 'en-US' ? ' selected' : '') + '>English</option>' +
        '</select>';

    document.getElementById('localeSwitcher').addEventListener('change', function (e) {
        switchLocale(e.target.value);
    });
}

// 页面加载时自动翻译静态 data-i18n 元素
document.addEventListener('DOMContentLoaded', function () {
    applyI18n(document);
});
