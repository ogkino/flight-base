<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

/**
 * 文件上传控制器
 */
class UploadController
{
    /**
     * 上传文件
     */
    public static function upload()
    {
        AuthMiddleware::checkAdmin();
        
        if (!isset($_FILES['file'])) {
            error('请选择文件');
        }
        
        $file = $_FILES['file'];
        
        // 获取全局上传配置
        $config = config('upload');
        $allowedExts = $config['allowed_types'] ?? ['jpg', 'png', 'gif'];
        $maxSize = $config['max_size'] ?? 10485760; // 默认 10MB
        
        // 简单的 MIME 类型映射
        $mimeMap = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed'],
            'txt' => ['text/plain'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation']
        ];
        
        $allowedMimes = [];
        foreach ($allowedExts as $ext) {
            $ext = strtolower($ext);
            if (isset($mimeMap[$ext])) {
                $allowedMimes = array_merge($allowedMimes, $mimeMap[$ext]);
            }
        }
        
        // 验证文件
        $result = validateUploadFile($file, $allowedMimes, $maxSize);
        
        if (!$result['success']) {
            error($result['msg']);
        }
        
        // 生成文件名
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = md5(uniqid() . time()) . '.' . $ext;
        
        // 按日期分目录
        $dateDir = date('Ymd');
        $uploadDir = __DIR__ . '/../../../public/uploads/' . $dateDir . '/';
        
        // 创建目录
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;
        
        // 移动文件
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // 返回相对路径
            $url = '/uploads/' . $dateDir . '/' . $filename;
            
            // 审计日志
            auditLog('上传文件', [
                'filename' => $filename,
                'url' => $url,
                'size' => $file['size']
            ]);
            
            success([
                'url' => $url,
                'filename' => $filename,
                'size' => $file['size']
            ], '上传成功');
        } else {
            error('文件上传失败');
        }
    }
}
