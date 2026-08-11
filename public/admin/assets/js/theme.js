/**
 * Flight Base — 管理后台多皮肤（Theme）
 *
 * 部署默认：AdminThemeConfig::DEFAULT_THEME → /api/admin/theme-config
 *   - 改 DEFAULT_THEME 后打开 login.html 即生效（会跳到对应 login_{id}.html）
 *   - 若当前入口是 login.html（主入口），壳页也会跟随新的部署默认
 *   - login_xxx.html 显式选型不会被部署默认打断
 *   - 注意：勿用 theme-config.js 后缀（Linux Nginx 常把 *.js 当静态资源 404）
 */
(function (global) {
    'use strict';

    var THEME_KEY = 'admin_theme';
    var LOGIN_KEY = 'admin_login_entry';
    var DEFAULT_TOKEN_KEY = 'admin_default_theme_token';
    var COOKIE_MAX_AGE = 365 * 24 * 60 * 60;
    var THEME_CSS_VERSION = '1.4.4';
    var MENU_LAYOUT_CSS_VERSION = '1.1.4';
    var THEME_ID_RE = /^[a-z0-9_-]+$/i;

    /**
     * 兜底映射（仅 theme-config.js 未加载时使用，如部分登录页）
     * 正式映射以 AdminThemeConfig::MENU_LAYOUT_BY_THEME 为准，由 theme-config.js 注入
     */
    var MENU_LAYOUT_BY_THEME_FALLBACK = {
        default: 'pill',
        teal: 'fill',
        dark: 'edge',
        ocean: 'capsule',
        wechat: 'cell',
        bootstrap: 'docs',
        element: 'edge',
        ant: 'fill',
        naive: 'capsule',
        arco: 'edge',
        tdesign: 'block',
        semi: 'fill',
        tabler: 'capsule',
        adminlte: 'fill',
        material: 'rail',
        carbon: 'edge',
        fluent: 'block',
        notion: 'minimal',
        cyber: 'edge'
    };

    function menuLayoutMap() {
        var fromServer = global.ADMIN_MENU_LAYOUT_BY_THEME;
        if (fromServer && typeof fromServer === 'object') return fromServer;
        return MENU_LAYOUT_BY_THEME_FALLBACK;
    }

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    function writeCookie(name, value) {
        document.cookie = name + '=' + encodeURIComponent(value)
            + '; path=/; max-age=' + COOKIE_MAX_AGE
            + '; SameSite=Lax';
    }

    function normalizeTheme(theme) {
        if (!theme || theme === 'default') return 'default';
        theme = String(theme).toLowerCase();
        return THEME_ID_RE.test(theme) ? theme : 'default';
    }

    function getConfiguredDefaultTheme() {
        return normalizeTheme(global.ADMIN_DEFAULT_THEME || 'default');
    }

    function defaultLoginEntryFor(theme) {
        theme = normalizeTheme(theme);
        return theme === 'default' ? 'login.html' : ('login_' + theme + '.html');
    }

    function getAdminTheme() {
        try {
            var fromLs = localStorage.getItem(THEME_KEY);
            if (fromLs !== null && fromLs !== '') return normalizeTheme(fromLs);
        } catch (e) { /* ignore */ }
        var fromCookie = readCookie(THEME_KEY);
        if (fromCookie) return normalizeTheme(fromCookie);
        return getConfiguredDefaultTheme();
    }

    function getAdminLoginEntry() {
        try {
            var fromLs = localStorage.getItem(LOGIN_KEY);
            if (fromLs) return fromLs;
        } catch (e) { /* ignore */ }
        var fromCookie = readCookie(LOGIN_KEY);
        if (fromCookie) return fromCookie;
        return defaultLoginEntryFor(getAdminTheme());
    }

    function getMenuLayout(theme) {
        theme = normalizeTheme(theme || getAdminTheme());
        var map = menuLayoutMap();
        return map[theme] || 'fill';
    }

    function cssBase() {
        return (global.ADMIN_THEME_CSS_BASE || '/admin/assets/css/').replace(/\/?$/, '/');
    }

    function setAdminTheme(theme, loginEntry) {
        theme = normalizeTheme(theme);
        if (!loginEntry) loginEntry = defaultLoginEntryFor(theme);
        try {
            localStorage.setItem(THEME_KEY, theme);
            localStorage.setItem(LOGIN_KEY, loginEntry);
        } catch (e) { /* ignore */ }
        writeCookie(THEME_KEY, theme);
        writeCookie(LOGIN_KEY, loginEntry);
        applyMenuLayout(theme);
        return theme;
    }

    function themeCssHref(theme) {
        return cssBase() + 'admin.theme.' + theme + '.css?v=' + THEME_CSS_VERSION;
    }

    function menuLayoutCssHref() {
        return cssBase() + 'admin.menu-layouts.css?v=' + MENU_LAYOUT_CSS_VERSION;
    }

    function applyMenuLayout(theme) {
        var layout = getMenuLayout(theme);
        document.documentElement.setAttribute('data-menu-layout', layout);
        return layout;
    }

    function ensureMenuLayoutCss() {
        var id = 'admin-menu-layout-css';
        var old = document.getElementById(id);
        if (old) old.parentNode.removeChild(old);
        var link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = menuLayoutCssHref();
        document.head.appendChild(link);
    }

    /**
     * 部署 DEFAULT_THEME 变更时：仅主入口（login.html / 尚无入口）跟随；
     * login_xxx 显式选型不覆盖。
     */
    function syncDeploymentDefaultTheme() {
        var token = global.ADMIN_DEFAULT_THEME_TOKEN || '';
        if (!token) return false;

        var prev = '';
        try { prev = localStorage.getItem(DEFAULT_TOKEN_KEY) || ''; } catch (e) { /* ignore */ }

        if (token === prev) return false;

        try { localStorage.setItem(DEFAULT_TOKEN_KEY, token); } catch (e) { /* ignore */ }

        var entry = '';
        try { entry = localStorage.getItem(LOGIN_KEY) || ''; } catch (e) { /* ignore */ }
        if (!entry) entry = readCookie(LOGIN_KEY) || '';

        // 主入口或首次访问 → 跟随新的部署默认
        if (!entry || entry === 'login.html') {
            var t = getConfiguredDefaultTheme();
            setAdminTheme(t, defaultLoginEntryFor(t));
            return true;
        }
        return false;
    }

    function applyAdminThemeCss() {
        syncDeploymentDefaultTheme();

        var theme = getAdminTheme();
        try {
            writeCookie(THEME_KEY, theme);
            writeCookie(LOGIN_KEY, getAdminLoginEntry());
        } catch (e) { /* ignore */ }

        document.documentElement.setAttribute('data-admin-theme', theme);
        applyMenuLayout(theme);

        var old = document.getElementById('admin-theme-css');
        if (old) old.parentNode.removeChild(old);

        if (theme !== 'default') {
            var link = document.createElement('link');
            link.id = 'admin-theme-css';
            link.rel = 'stylesheet';
            link.href = themeCssHref(theme);
            document.head.appendChild(link);
        }

        ensureMenuLayoutCss();
        return theme;
    }

    /**
     * 仅 login.html：强制切到部署默认（忽略旧 Cookie 里的其它 login_xxx 残留意图）
     */
    function applyPrimaryLoginTheme() {
        if (typeof global.ADMIN_DEFAULT_THEME === 'undefined') {
            try {
                console.warn(
                    '[theme] theme-config 未加载，DEFAULT_THEME 无法生效。'
                    + '请确认 Network 中 ../api/admin/theme-config 为 200，'
                    + '且响应含 window.ADMIN_DEFAULT_THEME=... '
                    + '（Linux Nginx 下勿用 .js 后缀，会被当成静态文件 404）'
                );
            } catch (e) { /* ignore */ }
        }

        var t = getConfiguredDefaultTheme();
        try {
            if (global.ADMIN_DEFAULT_THEME_TOKEN) {
                localStorage.setItem(DEFAULT_TOKEN_KEY, global.ADMIN_DEFAULT_THEME_TOKEN);
            }
        } catch (e) { /* ignore */ }

        if (t !== 'default') {
            // 先写入，再跳到完整布局登录页
            setAdminTheme(t, defaultLoginEntryFor(t));
            location.replace(defaultLoginEntryFor(t));
            return t;
        }
        setAdminTheme('default', 'login.html');
        return t;
    }

    global.ADMIN_THEME_KEY = THEME_KEY;
    global.ADMIN_LOGIN_KEY = LOGIN_KEY;
    global.getMenuLayoutMap = menuLayoutMap;
    global.getAdminTheme = getAdminTheme;
    global.getAdminLoginEntry = getAdminLoginEntry;
    global.getConfiguredDefaultTheme = getConfiguredDefaultTheme;
    global.getMenuLayout = getMenuLayout;
    global.setAdminTheme = setAdminTheme;
    global.applyAdminThemeCss = applyAdminThemeCss;
    global.applyMenuLayout = applyMenuLayout;
    global.applyPrimaryLoginTheme = applyPrimaryLoginTheme;
    global.themeCssHref = themeCssHref;
})(typeof window !== 'undefined' ? window : this);
