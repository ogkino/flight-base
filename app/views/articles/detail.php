<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title'] ?? '文章详情'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .back-btn { 
            display: inline-block; 
            color: #1E90FF; 
            text-decoration: none; 
            margin-bottom: 30px;
            font-size: 14px;
        }
        .back-btn:hover { text-decoration: underline; }
        h1 { color: #333; margin-bottom: 20px; line-height: 1.4; font-size: 32px; font-weight: 700; }
        .article-meta { 
            color: #999; 
            font-size: 14px; 
            padding-bottom: 20px; 
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }
        .article-meta span {
            margin-right: 20px;
        }
        .article-cover {
            width: 100%;
            max-height: 500px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .article-cover img {
            width: 100%;
            height: auto;
            display: block;
        }
        .article-content { 
            color: #333; 
            line-height: 1.8; 
            font-size: 16px;
        }
        /* 富文本内容样式 */
        .article-content h1 { font-size: 28px; margin: 30px 0 20px; border-bottom: 2px solid #1E90FF; padding-bottom: 10px; }
        .article-content h2 { font-size: 24px; margin: 25px 0 15px; color: #333; }
        .article-content h3 { font-size: 20px; margin: 20px 0 12px; color: #444; }
        .article-content h4 { font-size: 18px; margin: 18px 0 10px; color: #555; }
        .article-content p { margin: 15px 0; line-height: 1.8; }
        .article-content ul, .article-content ol { margin: 15px 0; padding-left: 30px; }
        .article-content li { margin: 8px 0; line-height: 1.8; }
        .article-content blockquote { 
            margin: 20px 0; 
            padding: 15px 20px; 
            background: #f9f9f9; 
            border-left: 4px solid #1E90FF; 
            color: #666;
        }
        .article-content pre { 
            background: #f5f5f5; 
            padding: 15px; 
            border-radius: 4px; 
            overflow-x: auto; 
            margin: 15px 0;
            border: 1px solid #e0e0e0;
        }
        .article-content code { 
            background: #f0f0f0; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-family: "Courier New", monospace;
            font-size: 14px;
        }
        .article-content pre code { 
            background: none; 
            padding: 0; 
        }
        .article-content a { color: #1E90FF; text-decoration: none; }
        .article-content a:hover { text-decoration: underline; }
        .article-content img { max-width: 100%; height: auto; border-radius: 4px; margin: 15px 0; }
        .article-content strong { font-weight: 600; color: #222; }
        .article-content em { font-style: italic; color: #555; }
        .article-content hr { margin: 30px 0; border: none; border-top: 1px solid #e0e0e0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/articles" class="back-btn">← 返回列表</a>
        
        <h1><?php echo htmlspecialchars($article['title']); ?></h1>
        
        <div class="article-meta">
            <span>👤 <?php echo htmlspecialchars($article['author'] ?? '匿名'); ?></span>
            <span>📅 <?php echo date('Y-m-d', strtotime($article['publish_date'] ?? $article['created_at'])); ?></span>
            <span>👁️ <?php echo $article['views'] ?? 0; ?> 次浏览</span>
        </div>
        
        <?php if (!empty($article['cover'])): ?>
            <div class="article-cover">
                <img src="<?php echo htmlspecialchars($article['cover']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
            </div>
        <?php endif; ?>
        
        <div class="article-content">
            <?php echo $article['content']; ?>
        </div>
    </div>
</body>
</html>
