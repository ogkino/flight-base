<?php
/**
 * Flight Base 框架入口文件
 *
 * 适用模式：PHP-FPM / FrankenPHP Classic
 * 每次 HTTP 请求独立启动完整 PHP 生命周期。
 *
 * FrankenPHP Worker 模式请使用 public/worker.php 作为入口。
 * 详见 docs/SERVER_MODES.md
 */

require __DIR__ . '/../app/bootstrap.php';

Flight::start();
