<?php
namespace App\Api;

use Gregwar\Captcha\CaptchaBuilder;

class CaptchaController
{
    /**
     * 生成验证码
     */
    public static function generate()
    {
        $builder = new CaptchaBuilder;
        $builder->build();
        
        // 存储到 Session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['captcha'] = $builder->getPhrase();
        
        // 输出图片
        header('Content-type: image/jpeg');
        $builder->output();
        exit;
    }
}
