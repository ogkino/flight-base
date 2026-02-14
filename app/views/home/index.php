<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Base - 轻量级 PHP 框架</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 1000px; 
            margin: 50px auto; 
            background: white; 
            padding: 60px 40px; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #333; 
            margin-bottom: 10px; 
            font-size: 48px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #eee;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .feature {
            padding: 25px;
            background: #f9f9f9;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .feature h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .feature p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        .links {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Flight Base</h1>
        <p class="subtitle">基于 Flight + Medoo + Layui 的超轻量级 PHP 框架</p>
        
        <div class="features">
            <div class="feature">
                <h3>✨ 超级轻量</h3>
                <p>核心代码不到 1000 行，学习成本低，上手快速</p>
            </div>
            <div class="feature">
                <h3>🤖 AI 友好</h3>
                <p>结构清晰，代码规范，便于 AI 辅助开发</p>
            </div>
            <div class="feature">
                <h3>⚡ 无需打包</h3>
                <p>使用 Layui，修改代码即刻生效，开发效率高</p>
            </div>
            <div class="feature">
                <h3>🎯 快速开发</h3>
                <p>适合快速开发 API 接口、管理后台和前端页面</p>
            </div>
        </div>
        
        <div class="links">
            <a href="/articles" class="btn btn-primary">📝 查看示例文章</a>
            <a href="/admin/login.html" class="btn btn-success">🔐 管理后台登录</a>
            <a href="/api/health" class="btn btn-secondary">🏥 健康检查 API</a>
        </div>
        
        <div class="footer">
            <p>Flight Base v2.0 | MIT License</p>
            <p style="margin-top: 10px;">技术栈：Flight + Medoo + Layui + PHP 7.4+</p>
        </div>
    </div>
</body>
</html>
