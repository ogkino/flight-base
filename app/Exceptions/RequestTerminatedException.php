<?php

namespace App\Exceptions;

/**
 * 请求终止异常
 *
 * 在 FrankenPHP Worker 模式下，不能使用 exit/die 终止请求处理，
 * 因为 exit 会直接杀死 worker 进程。
 *
 * 使用场景：
 *   - success() / error() / layuiTable() 输出响应后需要终止当前请求
 *   - checkPermission() 权限拒绝后需要终止
 *   - AdminViewController::renderError() 渲染错误页后需要终止
 *
 * 在 worker.php 的 frankenphp_handle_request 回调中会捕获此异常，
 * 将其视为正常的请求结束，不会影响 worker 进程的持续运行。
 */
class RequestTerminatedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Request terminated normally.');
    }
}
