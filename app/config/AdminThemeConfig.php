<?php
/**
 * 管理端主题配置（唯一源）
 *
 * - DEFAULT_THEME：部署默认主题
 * - MENU_LAYOUT_BY_THEME：主题 → 菜单范式（经 /api/admin/theme-config 下发给前端）
 * - 勿再改 theme.js 里的映射表；前端以本文件为准
 */
namespace App\config;

class AdminThemeConfig
{
    /**
     * 部署级默认主题。
     *
     * - 空字符串 ''：原始皮（仅 admin.css，主题 id 视为 default）
     * - 指定内置主题 id（如 'ocean'、'teal'）：无 Cookie/本地缓存时使用该主题；
     *   访问 login.html 时会跳转到对应 login_{id}.html（完整登录布局）
     *
     * login_{id}.html 选型模式不受影响，仍可随时覆盖当前主题。
     *
     * 生效：改完后打开 /admin/login.html（不要只刷 index）。
     * 仍不变则清 localStorage 的 admin_theme / admin_login_entry / admin_default_theme_token。
     * Worker 模式需重启进程。
     */
    public const DEFAULT_THEME = '';

    /**
     * 主题 ID → 菜单范式（本表为唯一源）
     * 范式见 public/admin/assets/css/admin.menu-layouts.css
     * 修改后硬刷新 index.html 即可（theme-config 无缓存）
     */
    public const MENU_LAYOUT_BY_THEME = [
        'default'   => 'pill',
        'teal'      => 'fill',
        'dark'      => 'edge',
        'ocean'     => 'capsule',
        'wechat'    => 'cell',
        'bootstrap' => 'docs',
        'element'   => 'edge',
        'ant'       => 'fill',
        'naive'     => 'capsule',
        'arco'      => 'edge',
        'tdesign'   => 'block',
        'semi'      => 'fill',
        'tabler'    => 'capsule',
        'adminlte'  => 'fill',
        'material'  => 'rail',
        'carbon'    => 'edge',
        'fluent'    => 'block',
        'notion'    => 'minimal',
        'cyber'     => 'edge',
    ];

    public static function normalizeTheme(?string $theme): string
    {
        if ($theme === null || $theme === '' || $theme === 'default') {
            return 'default';
        }
        $theme = strtolower($theme);
        return preg_match('/^[a-z0-9_-]+$/i', $theme) ? $theme : 'default';
    }

    /**
     * 解析部署默认主题（空配置 → default）
     */
    public static function resolvedDefaultTheme(): string
    {
        $raw = self::DEFAULT_THEME;
        if ($raw === null || trim((string) $raw) === '') {
            return 'default';
        }
        return self::normalizeTheme($raw);
    }

    public static function menuLayout(?string $theme): string
    {
        $theme = self::normalizeTheme($theme);
        return self::MENU_LAYOUT_BY_THEME[$theme] ?? 'fill';
    }

    /**
     * 输出前端可读的主题配置 JS（无鉴权）
     * 路由：GET /api/admin/theme-config（兼 GET /api/admin/theme-config.js）
     */
    public static function emitThemeConfigJs(): void
    {
        $theme = self::resolvedDefaultTheme();
        // 仅随 DEFAULT_THEME 变化；用于主入口感知「部署默认已改」
        $token = substr(hash('sha256', 'default-theme:' . $theme), 0, 16);

        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo 'window.ADMIN_DEFAULT_THEME=' . json_encode($theme, JSON_UNESCAPED_UNICODE) . ';';
        echo 'window.ADMIN_DEFAULT_THEME_TOKEN=' . json_encode($token, JSON_UNESCAPED_UNICODE) . ';';
        echo 'window.ADMIN_MENU_LAYOUT_BY_THEME=' . json_encode(self::MENU_LAYOUT_BY_THEME, JSON_UNESCAPED_UNICODE) . ';';
        echo 'window.ADMIN_CAPTCHA_ENABLED=' . (\App\api\CaptchaController::isEnabled() ? 'true' : 'false') . ';';
        terminateRequest();
    }
}
