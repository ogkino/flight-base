<?php
namespace App\middleware;

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
                'msg' => lang('auth.captcha_required'),
                'data' => null
            ]);
            Flight::stop();
            return; // Flight::stop() 不会中断脚本执行，必须显式 return 防止继续往下走
        }
        
        if (empty($_SESSION['captcha'])) {
            Flight::json([
                'code' => 400,
                'msg' => lang('auth.captcha_expired'),
                'data' => null
            ]);
            Flight::stop();
            return;
        }
        
        // 不区分大小写比较
        if (strtolower((string)$captcha) !== strtolower((string)$_SESSION['captcha'])) {
            // 验证失败，清除验证码，强制用户刷新
            unset($_SESSION['captcha']);
            
            Flight::json([
                'code' => 400,
                'msg' => lang('auth.captcha_error'),
                'data' => null
            ]);
            Flight::stop();
            return;
        }
        
        // 验证通过，清除验证码（防止重放）
        unset($_SESSION['captcha']);
    }
}
