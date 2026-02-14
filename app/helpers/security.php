<?php
/**
 * 安全辅助函数
 * 
 * 提供输入验证、XSS防护、CSRF防护等安全功能
 */

/**
 * XSS 防护：过滤 HTML 标签
 */
function cleanInput($data)
{
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * 验证邮箱格式
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * 验证手机号（中国大陆）
 */
function validatePhone($phone)
{
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

/**
 * 验证用户名（字母、数字、下划线，4-20位）
 */
function validateUsername($username)
{
    return preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username);
}

/**
 * 验证密码强度（至少6位）
 */
function validatePassword($password)
{
    return strlen($password) >= 6;
}

/**
 * 启动 Session (如果未启动)
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 生成 CSRF Token
 */
function generateCsrfToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * 验证 CSRF Token
 */
function validateCsrfToken($token)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 限流检查（简单实现）
 * 
 * @param string $key 唯一标识（如 IP + 操作）
 * @param int $maxAttempts 最大尝试次数
 * @param int $timeWindow 时间窗口（秒）
 * @return bool 是否允许继续
 */
function checkRateLimit($key, $maxAttempts = 10, $timeWindow = 60)
{
    $cacheFile = __DIR__ . '/../../runtime/rate_limit_' . md5($key) . '.txt';
    
    // 确保目录存在
    $dir = dirname($cacheFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $now = time();
    $attempts = [];
    
    // 读取历史记录
    if (file_exists($cacheFile)) {
        $content = file_get_contents($cacheFile);
        $attempts = json_decode($content, true) ?: [];
    }
    
    // 清理过期记录
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // 检查是否超限
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    // 记录本次尝试
    $attempts[] = $now;
    file_put_contents($cacheFile, json_encode($attempts));
    
    return true;
}

/**
 * IP 白名单检查
 */
function checkIpWhitelist(array $whitelist = [])
{
    if (empty($whitelist)) {
        return true; // 未配置白名单，允许所有
    }
    
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    
    foreach ($whitelist as $allowedIp) {
        // 支持通配符，如 192.168.1.*
        $pattern = str_replace(['*', '.'], ['.*', '\.'], $allowedIp);
        if (preg_match('/^' . $pattern . '$/', $clientIp)) {
            return true;
        }
    }
    
    return false;
}

/**
 * 安全的文件上传检查
 */
function validateUploadFile($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 2097152)
{
    // 检查是否有错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'msg' => '文件上传失败'];
    }
    
    // 检查文件大小
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'msg' => '文件大小超过限制'];
    }
    
    // 检查文件类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'msg' => '不允许的文件类型'];
    }
    
    return ['success' => true];
}

/**
 * SQL 注入检测（额外防护，Medoo 已有预处理）
 */
function detectSqlInjection($input)
{
    $dangerous = [
        'union', 'select', 'insert', 'update', 'delete', 'drop', 
        'create', 'alter', 'exec', 'script', '--', '/*', '*/'
    ];
    
    $inputLower = strtolower($input);
    
    foreach ($dangerous as $keyword) {
        if (strpos($inputLower, $keyword) !== false) {
            writeLog('检测到可疑 SQL 输入: ' . $input, 'warning');
            return true;
        }
    }
    
    return false;
}

/**
 * 记录操作日志（审计）
 */
function auditLog($action, $details = [], $userId = null)
{
    if (!$userId) {
        $currentUser = Flight::get('currentUser');
        $userId = $currentUser['id'] ?? 0;
    }
    
    $logData = [
        'user_id' => $userId,
        'action' => $action,
        'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // 可以写入数据库或日志文件
    writeLog("审计日志: {$action} - " . json_encode($details, JSON_UNESCAPED_UNICODE), 'audit');
}
