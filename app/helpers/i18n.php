<?php
/**
 * 多语言（i18n）辅助函数
 *
 * 设计原则：
 * - 语言探测优先级：请求头 X-Locale > 查询参数 lang > Cookie admin_locale > 配置默认语言
 * - 语言包位于 app/lang/{locale}.php，返回按 section.key 组织的关联数组
 * - lang() 找不到 key 时会自动回退到基准语言包（zh-CN），仍找不到则返回 key 本身，方便开发期发现漏翻译
 *
 * 详细开发标准见 docs/I18N.md
 */

/**
 * 获取当前请求应使用的语言
 *
 * 注意：这里【不能】用 static 变量缓存结果——Worker 模式下本文件只在进程启动时
 * require 一次，static 变量会跨请求持续存在，导致后续所有请求都复用第一个请求
 * 探测到的语言。因此每次调用都重新从请求头/参数/Cookie 探测，开销极小（几次数组查找）。
 */
function currentLocale(): string
{
    $supported = config('supported_locales', ['zh-CN', 'en-US']);
    $default   = config('locale', 'zh-CN');

    $candidate = null;

    // 1. 请求头 X-Locale（前端 request() 已自动附带，见 config.js）
    if (function_exists('getHeader')) {
        $candidate = getHeader('X-Locale') ?: null;
    }

    // 2. 查询参数 ?lang=（用于直接访问的页面，如 View 模式、前端页面）
    if (!$candidate && function_exists('getQuery')) {
        $candidate = getQuery('lang') ?: null;
    }

    // 3. Cookie（config.js 的 setLocale() 会同步写入，供 PHP 渲染页读取）
    if (!$candidate && isset($_COOKIE['admin_locale'])) {
        $candidate = $_COOKIE['admin_locale'];
    }

    return in_array($candidate, $supported, true) ? $candidate : $default;
}

/**
 * 加载语言包（带静态缓存，Worker 模式下同一进程内只解析一次文件）
 */
function loadLangPack(string $locale): array
{
    static $cache = [];

    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $file = __DIR__ . '/../lang/' . $locale . '.php';
    $cache[$locale] = file_exists($file) ? require $file : [];

    return $cache[$locale];
}

/**
 * 按 "section.key" 点号路径读取数组值，找不到返回 null
 */
function i18nArrayGet(array $arr, string $key)
{
    if (array_key_exists($key, $arr)) {
        return $arr[$key];
    }

    $value = $arr;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }
        $value = $value[$segment];
    }

    return $value;
}

/**
 * 翻译函数
 *
 * @param string      $key     语言 key，如 'menu.dashboard'
 * @param array       $replace 占位符替换，如 ['name' => 'Tom'] 替换文案中的 :name
 * @param string|null $locale  指定语言，默认使用 currentLocale()
 */
function lang(string $key, array $replace = [], ?string $locale = null): string
{
    $locale = $locale ?: currentLocale();

    $text = i18nArrayGet(loadLangPack($locale), $key);

    // 回退到基准语言包
    if ($text === null && $locale !== 'zh-CN') {
        $text = i18nArrayGet(loadLangPack('zh-CN'), $key);
    }

    // 仍未找到，返回 key 本身（提示开发者存在漏翻译的 key）
    if ($text === null) {
        $text = $key;
    }

    foreach ($replace as $k => $v) {
        $text = str_replace(':' . $k, $v, $text);
    }

    return $text;
}

/**
 * ============================================================
 * 字段级多语言后缀（用于手写 CrudConfig.php，无需维护语言包 key）
 * ============================================================
 *
 * 约定：在配置数组里，任意 key 的兄弟 key 若命名为 `{key}_{后缀}`
 * （后缀见 app.php 的 locale_field_suffixes 配置），则视为该 key 在对应
 * 语言下的文案，例如：
 *
 *   ['label' => '昵称', 'label_en' => 'Nickname']
 *   ['title' => '标题', 'title_en' => 'Title']
 *   ['placeholder' => '请输入手机号', 'placeholder_en' => 'Enter phone number']
 *
 * 当前语言为 en-US 时，'label' 会被自动替换为 'Nickname'；无论最终使用的是
 * 哪种语言，所有 `_en` 结尾的后缀 key 都会从返回结果中移除，保持响应干净、
 * 不给前端暴露多余字段。
 *
 * 用途：菜单/统计等"框架级、少量、可复用"的文案用 lang() + app/lang/*.php；
 * 而业务模块里"大量、一次性、field 级"的文案（表单 label、表格 title、
 * placeholder、tips、按钮 text 等）用这个更轻量的后缀约定，
 * 不需要额外维护语言包文件。CRUD 设计器（crud-designer.html）不支持
 * 可视化编辑这些后缀字段，需要开发者直接手写 CrudConfig.php。
 *
 * 详见 docs/I18N.md
 */

/**
 * 对 ConfigController 返回的 CrudConfig 数组做字段级多语言替换
 *
 * @param mixed $data CrudConfig::xxx() 或 getMenus() 的返回值
 */
function localizeConfig($data)
{
    if (!is_array($data)) {
        return $data;
    }

    return i18nApplyFieldSuffix($data, i18nCurrentFieldSuffix());
}

/**
 * 获取当前语言对应的字段后缀（默认语言返回 null，表示无需替换）
 */
function i18nCurrentFieldSuffix(): ?string
{
    $map = config('locale_field_suffixes', []);
    return $map[currentLocale()] ?? null;
}

/**
 * 获取所有已配置的字段后缀（不含 null），用于识别需要从响应里清理掉的 key
 */
function i18nAllFieldSuffixes(): array
{
    static $suffixes = null;

    // 这里可以用 static 缓存：locale_field_suffixes 是静态配置，不随请求变化，
    // 与 currentLocale() 不同，不存在 Worker 模式下跨请求污染的问题。
    if ($suffixes === null) {
        $suffixes = array_values(array_filter(config('locale_field_suffixes', [])));
    }

    return $suffixes;
}

/**
 * 递归处理数组：替换命中的后缀字段，并清理所有语言后缀 key
 */
function i18nApplyFieldSuffix(array $data, ?string $suffix): array
{
    $isAssoc = i18nIsAssocArray($data);

    // 只有关联数组（而非纯索引列表）才可能存在 "字段 + 语言后缀" 的兄弟关系
    if ($isAssoc && $suffix) {
        foreach ($data as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $altKey = $key . '_' . $suffix;
            if (isset($data[$altKey]) && is_string($data[$altKey]) && $data[$altKey] !== '') {
                $data[$key] = $data[$altKey];
            }
        }
    }

    // 无论本次是否命中替换，所有语言后缀 key 都不应该出现在最终响应里
    if ($isAssoc) {
        $allSuffixes = i18nAllFieldSuffixes();
        foreach (array_keys($data) as $key) {
            if (!is_string($key)) {
                continue;
            }
            foreach ($allSuffixes as $sfx) {
                if (substr($key, -(strlen($sfx) + 1)) === '_' . $sfx) {
                    unset($data[$key]);
                    break;
                }
            }
        }
    }

    // 递归处理子数组（表单字段、表格列、下拉选项、菜单分组/子项等都是嵌套结构）
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = i18nApplyFieldSuffix($value, $suffix);
        }
    }

    return $data;
}

/**
 * 判断是否为关联数组（区别于纯索引列表 [0,1,2...]）
 */
function i18nIsAssocArray(array $arr): bool
{
    if ($arr === []) {
        return false;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
}
