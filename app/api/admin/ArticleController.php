<?php
namespace App\Api\Admin;

use Flight;
use App\Middleware\AuthMiddleware;

/**
 * 文章管理控制器
 * 
 * 这是真正处理业务逻辑的地方：
 * - list() 返回文章列表数据
 * - export() 导出文章
 * - create() 创建新文章
 * - update() 更新文章
 * - delete() 删除文章
 */
class ArticleController
{
    /**
     * 文章列表
     */
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('articles', 'list');
        
        $db = db();
        
        // 获取分页参数
        $page = (int)getQuery('page', 1);
        $limit = (int)getQuery('limit', 10);
        $keyword = getQuery('keyword', '');
        
        // 构建查询条件
        $where = [];
        if ($keyword) {
            $where['OR'] = [
                'title[~]' => $keyword,
                'author[~]' => $keyword
            ];
        }
        
        // 查询总数
        $count = $db->count('articles', $where);
        
        // 查询数据
        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $where['ORDER'] = ['id' => 'DESC'];
        
        $articles = $db->select('articles', '*', $where);
        
        // 返回 Layui 表格格式
        layuiTable($articles, $count);
    }
    
    /**
     * 导出文章
     */
    public static function export()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('articles', 'export');
        
        $db = db();
        $keyword = getQuery('keyword', '');
        
        $where = [];
        if ($keyword) {
            $where['OR'] = [
                'title[~]' => $keyword,
                'author[~]' => $keyword
            ];
        }
        $where['ORDER'] = ['id' => 'DESC'];
        
        $articles = $db->select('articles', '*', $where);
        
        // 导出 CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="articles_'.date('YmdHis').'.csv"');
        
        $fp = fopen('php://output', 'w');
        // 写入表头 (BOM for Excel)
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['ID', '标题', '作者', '创建时间', '状态']);
        
        foreach ($articles as $item) {
            fputcsv($fp, [
                $item['id'],
                $item['title'],
                $item['author'],
                $item['created_at'],
                $item['is_published'] ? '已发布' : '草稿'
            ]);
        }
        
        fclose($fp);
    }

    /**
     * 创建文章
     */
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('articles', 'create');
        
        $db = db();
        
        $data = [
            'title' => getPost('title'),
            'author' => getPost('author'),
            'content' => getPost('content'),
            'cover' => getPost('cover', ''),
            'publish_date' => getPost('publish_date', date('Y-m-d')),
            'is_published' => getPost('is_published', 1),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('articles', $data);
        
        if ($result->rowCount() > 0) {
            writeLog('创建文章：' . $data['title'], 'info');
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
    
    /**
     * 更新文章
     */
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('articles', 'update');
        
        $db = db();
        
        // 获取所有可能的字段
        $fields = ['title', 'author', 'content', 'cover', 'publish_date', 'is_published'];
        $data = [];
        
        foreach ($fields as $field) {
            $value = getPost($field);
            // 只有当前端传递了该字段时才更新（支持部分更新）
            if ($value !== null) {
                $data[$field] = $value;
            }
        }
        
        if (empty($data)) {
            error('没有收到更新数据');
            return;
        }
        
        $result = $db->update('articles', $data, [
            'id' => $id
        ]);
        
        writeLog('更新文章 ID: ' . $id, 'info');
        success([], '更新成功');
    }
    
    /**
     * 删除文章
     */
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('articles', 'delete');
        
        $db = db();
        
        $db->delete('articles', [
            'id' => $id
        ]);
        
        writeLog('删除文章 ID: ' . $id, 'info');
        success([], '删除成功');
    }
}
