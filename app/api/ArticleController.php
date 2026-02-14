<?php
namespace App\Api;

use Flight;

/**
 * 文章控制器
 */
class ArticleController
{
    /**
     * 显示文章列表页面
     */
    public static function listPage()
    {
        $db = db();
        
        // 查询文章列表（作者id关联用户表id，显示user表的nickname字段）
        $articles = $db->select('articles', [
            '[>]users' => ['author' => 'id']
        ], [
            'articles.id',
            'articles.title',
            'articles.content',
            'articles.views',
            'articles.is_published',
            'articles.created_at',
            'articles.updated_at',
            'users.nickname(author)'
        ], [
            'articles.is_published' => 1,
            'ORDER' => ['articles.id' => 'ASC'],
            'LIMIT' => 20
        ]);
        
        
        // 渲染视图
        Flight::render('articles/list', [
            'title' => '最新文章',
            'articles' => $articles

        ]);
    }
    
    /**
     * 显示文章详情页面
     * 
     * 功能说明：
     * 1. 查询文章信息（仅已发布）
     * 2. 自动增加浏览量（views +1）
     * 3. 渲染详情页面
     */
    public static function detailPage($id)
    {
        $db = db();
        
        // 查询文章
        $article = $db->get('articles', [
            '[>]users' => ['author' => 'id']
        ], [
            'articles.id',
            'articles.title',
            'articles.content',
            'articles.views',
            'articles.is_published',
            'articles.created_at',
            'articles.updated_at',
            'users.nickname(author)'
        ], [
            'articles.id' => $id,
            'articles.is_published' => 1
        ]);

        
        if (!$article) {
            Flight::render('errors/404', [
                'message' => '文章不存在'
            ]);
            return;
        }
        
        // 浏览量 +1（使用 Medoo 的原子操作）
        $db->update('articles', [
            'views[+]' => 1  // 使用 [+] 表示字段值 +1
        ], [
            'id' => $id
        ]);
        
        // 更新文章数组中的浏览量（用于页面显示）
        $article['views'] = ($article['views'] ?? 0) + 1;
        
        // 渲染视图
        Flight::render('articles/detail', [
            'article' => $article
        ]);
    }
    
    /**
     * API: 获取文章列表（JSON）
     */
    public static function listApi()
    {
        $db = db();
        
        $page = (int)getQuery('page', 1);
        $limit = (int)getQuery('limit', 10);
        
        $count = $db->count('articles', ['is_published' => 1]);
        
        $articles = $db->select('articles', '*', [
            'is_published' => 1,
            'ORDER' => ['created_at' => 'DESC'],
            'LIMIT' => [($page - 1) * $limit, $limit]
        ]);
        
        success([
            'list' => $articles,
            'total' => $count,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * API: 获取文章详情（JSON）
     * 
     * 功能说明：
     * 1. 查询文章信息（仅已发布）
     * 2. 自动增加浏览量（views +1）
     * 3. 返回 JSON 格式数据
     */
    public static function detailApi($id)
    {
        $db = db();
        
        // 查询文章
        $article = $db->get('articles', '*', [
            'id' => $id,
            'is_published' => 1
        ]);
        
        if (!$article) {
            error('文章不存在', 404);
            return;
        }
        
        // 浏览量 +1（使用 Medoo 的原子操作）
        $db->update('articles', [
            'views[+]' => 1  // 使用 [+] 表示字段值 +1
        ], [
            'id' => $id
        ]);
        
        // 更新文章数组中的浏览量（返回给前端）
        $article['views'] = ($article['views'] ?? 0) + 1;
        
        success($article);
    }
}
