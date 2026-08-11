<?php
namespace App\api;

use Gregwar\Captcha\CaptchaBuilder;

class CaptchaController
{
    private const TTL = 300; // 验证码有效期 5 分钟

    /** 是否启用登录验证码（.env ADMIN_CAPTCHA_ENABLED，默认 true） */
    public static function isEnabled(): bool
    {
        return (bool) config('captcha_enabled', true);
    }

    /**
     * 生成验证码
     *
     * - 默认：返回 JPEG 图片（管理后台 <img src="/api/captcha">），答案写入 Session
     * - ?format=json：返回 JSON（Flutter / 原生客户端推荐），含 captcha_key + base64 图，不依赖 Cookie
     * - 关闭验证码时：JSON 返回 enabled=false；图片模式返回 1x1 占位图
     *
     * GET /api/captcha
     * GET /api/captcha?format=json
     */
    public static function generate()
    {
        $format = strtolower((string)(getQuery('format', '') ?: ''));
        $wantJson = $format === 'json'
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if (!self::isEnabled()) {
            if ($wantJson) {
                success([
                    'enabled'          => false,
                    'captcha_required' => false,
                ]);
                return;
            }
            // 1x1 透明 GIF，避免登录页 <img> 破图
            header('Content-Type: image/gif');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('X-Captcha-Enabled: 0');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            terminateRequest();
            return;
        }

        $builder = new CaptchaBuilder;
        $builder->build();
        $phrase = $builder->getPhrase();
        $captchaKey = bin2hex(random_bytes(16));

        self::storePhrase($captchaKey, $phrase);

        // 顺便清理过期验证码文件（每次生成时顺带扫一遍，无需另起定时任务）
        self::cleanupExpired();

        // Session 兜底：浏览器管理端仍可用纯图片模式
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['captcha'] = $phrase;
        $_SESSION['captcha_key'] = $captchaKey;

        if ($wantJson) {
            ob_start();
            $builder->output();
            $binary = ob_get_clean();
            $base64 = base64_encode($binary);

            success([
                'enabled'        => true,
                'captcha_required' => true,
                'captcha_key'    => $captchaKey,
                'expires_in'     => self::TTL,
                'image_base64'   => $base64,
                'image_data_uri' => 'data:image/jpeg;base64,' . $base64,
            ]);
            return;
        }

        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Captcha-Key: ' . $captchaKey);
        header('X-Captcha-Enabled: 1');
        $builder->output();
        terminateRequest();
    }

    /**
     * 将验证码答案写入 runtime 文件（供 captcha_key 校验，不依赖 Cookie）
     */
    public static function storePhrase(string $key, string $phrase): void
    {
        $dir = self::storageDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = json_encode([
            'phrase' => $phrase,
            'exp'    => time() + self::TTL,
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents($dir . DIRECTORY_SEPARATOR . $key . '.json', $payload);
    }

    /**
     * 按 key 取出并删除答案；过期或不存在返回 null
     */
    public static function consumePhrase(string $key): ?string
    {
        $key = preg_replace('/[^a-f0-9]/i', '', $key);
        if ($key === '' || strlen($key) < 16) {
            return null;
        }
        $file = self::storageDir() . DIRECTORY_SEPARATOR . $key . '.json';
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        @unlink($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data) || empty($data['phrase']) || empty($data['exp'])) {
            return null;
        }
        if ((int)$data['exp'] < time()) {
            return null;
        }
        return (string)$data['phrase'];
    }

    private static function storageDir(): string
    {
        return dirname(__DIR__, 2) . '/runtime/captcha';
    }

    /**
     * 删除过期的 captcha_key 文件（登录成功会主动删；失败/过期的在此清理）
     */
    public static function cleanupExpired(): void
    {
        $dir = self::storageDir();
        if (!is_dir($dir)) {
            return;
        }
        $now = time();
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            $data = json_decode((string)$raw, true);
            if (!is_array($data) || empty($data['exp']) || (int)$data['exp'] < $now) {
                @unlink($file);
            }
        }
    }
}
