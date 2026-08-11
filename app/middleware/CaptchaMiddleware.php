<?php
namespace App\middleware;

use Flight;
use App\api\CaptchaController;

class CaptchaMiddleware
{
    /**
     * 验证验证码
     *
     * 优先使用 Body 中的 captcha_key（Flutter / 原生推荐，不依赖 Cookie）；
     * 未传 captcha_key 时回退到 PHP Session（管理后台图片模式）。
     *
     * .env ADMIN_CAPTCHA_ENABLED=false 时整段跳过。
     */
    public static function handle()
    {
        if (!\App\api\CaptchaController::isEnabled()) {
            return;
        }

        $data = Flight::request()->data;
        $captcha = isset($data->captcha) ? trim((string)$data->captcha) : '';
        $captchaKey = isset($data->captcha_key) ? trim((string)$data->captcha_key) : '';

        if ($captcha === '') {
            self::fail(lang('auth.captcha_required'));
            return;
        }

        // ① 推荐：captcha_key 文件存储（跨端、无需 Session Cookie）
        if ($captchaKey !== '') {
            $phrase = CaptchaController::consumePhrase($captchaKey);
            if ($phrase === null) {
                self::fail(lang('auth.captcha_expired'));
                return;
            }
            if (strtolower($captcha) !== strtolower($phrase)) {
                self::fail(lang('auth.captcha_error'));
                return;
            }
            return;
        }

        // ② 兼容：Session（浏览器管理端）
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['captcha'])) {
            self::fail(lang('auth.captcha_expired'));
            return;
        }

        if (strtolower($captcha) !== strtolower((string)$_SESSION['captcha'])) {
            unset($_SESSION['captcha'], $_SESSION['captcha_key']);
            self::fail(lang('auth.captcha_error'));
            return;
        }

        unset($_SESSION['captcha'], $_SESSION['captcha_key']);
    }

    private static function fail(string $msg): void
    {
        // 与 error() 一致走统一 JSON；此处保留显式输出以兼容历史行为
        if (function_exists('error')) {
            error($msg, 400);
            return;
        }
        Flight::json(['code' => 400, 'msg' => $msg, 'data' => null]);
        Flight::stop();
    }
}
