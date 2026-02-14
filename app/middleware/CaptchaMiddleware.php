<?php
namespace App\Middleware;

use Flight;

class CaptchaMiddleware
{
    /**
     * 验证验证码
     */
    public static function handle()
    {
        $data = Flight::request()->data;
        $captcha = $data->captcha;
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($captcha)) {
            Flight::json([
                'code' => 400,
                'msg' => '请输入验证码',
                'data' => null
            ]);
            Flight::stop();
        }
        
        if (empty($_SESSION['captcha'])) {
            Flight::json([
                'code' => 400,
                'msg' => '验证码已失效，请刷新',
                'data' => null
            ]);
            Flight::stop();
        }
        
        // 不区分大小写比较
        if (strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
            // 验证失败，清除验证码，强制用户刷新
            unset($_SESSION['captcha']);
            
            Flight::json([
                'code' => 400,
                'msg' => '验证码错误',
                'data' => null
            ]);
            Flight::stop();
        }
        
        // 验证通过，清除验证码（防止重放）
        unset($_SESSION['captcha']);
    }
}
