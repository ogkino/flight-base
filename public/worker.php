<?php
/**
 * Flight Base 框架入口文件 —— FrankenPHP Worker 模式
 *
 * 适用模式：FrankenPHP Worker
 * PHP 进程长驻内存，通过 frankenphp_handle_request() 循环处理多个请求。
 * bootstrap.php 中的初始化代码（DB连接、路由注册等）只执行一次，
 * 显著减少每次请求的开销，性能大幅提升。
 *
 * PHP-FPM / Classic 模式请使用 public/index.php 作为入口。
 * 详见 docs/SERVER_MODES.md
 */

// 声明当前运行在 Worker 模式
// functions.php 中的 terminateRequest() 会检测此常量，
// 以决定是抛出异常（Worker）还是直接 exit（FPM/Classic）
define('APP_WORKER_MODE', true);

// 初始化：只执行一次（进程启动时）
// 包含所有路由注册、DB连接、中间件挂载
require __DIR__ . '/../app/bootstrap.php';

// Worker 请求循环
// frankenphp_handle_request 由 FrankenPHP 扩展在运行时提供，IDE 可能报"未定义函数"，可忽略
/** @phpstan-ignore-next-line */
do {
    $running = frankenphp_handle_request(function () {

        // ── 每次请求开始前，清除上一个请求残留的用户状态 ──
        // 这是 Worker 模式的安全关键步骤：
        // Flight 是单例，Flight::set() 的值会跨请求持久。
        // 不清除会导致上一个请求的登录状态"泄漏"给下一个请求。
        Flight::set('currentUser', null);
        Flight::set('userType', null);
        Flight::set('admin_id', null);

        // ── 每次请求独立开启 Session ──
        // security.php 的顶层 session_start() 在 Worker 模式下已被移除，
        // 这里负责在每次请求时按需启动 session。
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            Flight::start();
        } catch (\App\Exceptions\RequestTerminatedException $e) {
            // 正常的请求终止（由 success/error/layuiTable 等函数触发）
            // 不是错误，什么都不做，继续处理下一个请求
        }
    });

    // 帮助 PHP GC 在请求间回收内存（可选，但建议保留）
    gc_collect_cycles();

} while ($running);
