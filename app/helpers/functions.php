<?php
/**
 * 全局辅助函数
 */

/**
 * 终止当前请求处理（模式感知）
 *
 * - Worker 模式（APP_WORKER_MODE=true）：抛出 RequestTerminatedException
 *   由 worker.php 的 frankenphp_handle_request 回调捕获，进程继续运行
 * - FPM / Classic 模式：直接 exit，传统行为
 */
function terminateRequest(): void
{
    if (defined('APP_WORKER_MODE') && APP_WORKER_MODE) {
        throw new \App\Exceptions\RequestTerminatedException();
    }
    exit;
}

/**
 * 返回成功的 JSON 响应
 */
function success($data = [], $msg = 'success', $code = 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    Flight::stop();
    terminateRequest();
}

/**
 * 返回失败的 JSON 响应
 */
function error($msg = 'error', $code = 1, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    Flight::stop();
    terminateRequest();
}

/**
 * 返回 Layui 表格专用格式
 * Layui 表格需要 count 字段用于分页
 */
function layuiTable($data = [], $count = 0, $msg = '', $code = 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code'  => $code,
        'msg'   => $msg,
        'count' => $count,
        'data'  => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    Flight::stop();
    terminateRequest();
}

/**
 * 获取 POST 数据
 */
function getPost($key = null, $default = null) {
    $data = Flight::request()->data->getData();

    // 兼容处理：如果 Flight 没解析到数据，且是 JSON 请求，尝试手动解析
    if (empty($data)) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? getHeader('Content-Type');
        if (stripos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $json  = json_decode($input, true);
            if (is_array($json)) {
                $data = $json;
            }
        }
    }

    // 如果还是空的，尝试合并 $_POST
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    return $key ? ($data[$key] ?? $default) : $data;
}

/**
 * 获取 GET 参数
 */
function getQuery($key = null, $default = null) {
    $query = Flight::request()->query;
    return $key ? ($query[$key] ?? $default) : $query;
}

/**
 * 获取请求头
 */
function getHeader($key) {
    return Flight::request()->getHeader($key);
}

/**
 * 生成 Token
 */
function generateToken($user) {
    $config = require __DIR__ . '/../config/app.php';

    return base64_encode(json_encode([
        'id'       => $user['id'],
        'username' => $user['username'],
        'type'     => $user['type'] ?? 'user',
        'exp'      => time() + $config['token_expire']
    ]));
}

/**
 * 验证 Token
 */
function validateToken($token) {
    if (!$token) return false;

    $data = json_decode(base64_decode($token), true);
    if (!$data || !isset($data['exp']) || $data['exp'] < time()) {
        return false;
    }

    return $data;
}

/**
 * 获取当前登录用户
 */
function currentUser() {
    return Flight::get('currentUser');
}

/**
 * 获取数据库实例
 */
function db() {
    return Flight::db();
}

/**
 * 获取配置
 */
function config($key = null, $default = null) {
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

/**
 * 写入日志
 */
function writeLog($message, $level = 'info') {
    $config = config('log');

    if (!$config['enabled']) {
        return;
    }

    $logFile = $config['path'] . date('Y-m-d') . '.log';
    $time    = date('Y-m-d H:i:s');
    $content = "[{$time}] [{$level}] {$message}" . PHP_EOL;

    @file_put_contents($logFile, $content, FILE_APPEND);
}

/**
 * 密码加密
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 验证密码
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * 生成随机字符串
 */
function randomString($length = 16) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $str   = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}
