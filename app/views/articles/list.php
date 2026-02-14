<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? '文章列表'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #1E90FF; padding-bottom: 10px; }
        .article-list { list-style: none; }
        .article-item { 
            display: flex;
            gap: 20px;
            border-bottom: 1px solid #eee; 
            padding: 20px 0; 
            transition: all 0.3s;
        }
        .article-item:hover { background: #f9f9f9; padding: 20px; margin: 0 -20px; border-radius: 4px; }
        .article-cover {
            flex-shrink: 0;
            width: 200px;
            height: 140px;
            overflow: hidden;
            border-radius: 6px;
            background: #f0f0f0;
        }
        .article-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .article-cover.no-cover {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 14px;
        }
        .article-info { flex: 1; }
        .article-title { font-size: 20px; margin-bottom: 10px; font-weight: 600; }
        .article-title a { color: #333; text-decoration: none; }
        .article-title a:hover { color: #1E90FF; }
        .article-meta { color: #999; font-size: 13px; margin-bottom: 10px; }
        .article-meta span { margin-right: 15px; }
        .article-excerpt { color: #666; line-height: 1.8; font-size: 14px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .btn { 
            display: inline-block; 
            padding: 12px 30px; 
            background: #1E90FF; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            margin-top: 30px;
            transition: background 0.3s;
            font-size: 14px;
        }
        .btn:hover { background: #0066CC; }
        .empty { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($title ?? '最新文章'); ?></h1>

        <?php if (empty($articles)): ?>
            <div class="empty">
                <div class="empty-icon">📝</div>
                <p style="font-size: 16px; margin-bottom: 10px;">暂无文章</p>
                <p style="font-size: 14px;">请先在后台添加文章</p>
            </div>
        <?php else: ?>
            <ul class="article-list">
                <?php foreach ($articles as $article): ?>
                    <li class="article-item">
                        <?php if (!empty($article['cover'])): ?>
                            <div class="article-cover">
                                <img src="<?php echo htmlspecialchars($article['cover']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                            </div>
                        <?php else: ?>
                            <div class="article-cover no-cover">暂无封面</div>
                        <?php endif; ?>
                        
                        <div class="article-info">
                            <div class="article-title">
                                <a href="/article/<?php echo $article['id']; ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </div>
                            <div class="article-meta">
                                <span>👤 <?php echo htmlspecialchars($article['author'] ?? '匿名'); ?></span>
                                <span>📅 <?php echo date('Y-m-d', strtotime($article['publish_date'] ?? $article['created_at'])); ?></span>
                                <span>👁️ <?php echo $article['views'] ?? 0; ?> 次浏览</span>
                            </div>
                            <div class="article-excerpt">
                                <?php 
                                    // 从富文本中提取纯文本摘要
                                    $content = strip_tags($article['content']);
                                    echo htmlspecialchars(mb_substr($content, 0, 120));
                                    if (mb_strlen($content) > 120) echo '...';
                                ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <a href="/admin/index.html" class="btn">进入管理后台</a>
    </div>
</body>
</html>
