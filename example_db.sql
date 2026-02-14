-- ==========================================
-- Flight Base 数据库结构
-- ==========================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `flight_base` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `flight_base`;

-- ==========================================
-- 管理员表
-- ==========================================
DROP TABLE IF EXISTS `og_admin`;
CREATE TABLE `og_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `permissions` text DEFAULT NULL COMMENT '权限配置（JSON格式）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `expired_at` int(11) DEFAULT '0' COMMENT '过期时间（Unix时间戳）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 插入默认管理员（用户名：admin 密码：password）
INSERT INTO `og_admin` (`id`, `username`, `password`, `nickname`, `permissions`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', NULL, 1);
-- 注意：
-- 1. 密码是 password 经过 password_hash 加密后的结果
-- 2. ID=1 的管理员 permissions 为 NULL 表示拥有所有权限

-- 插入测试管理员（用户名：test 密码：password）
INSERT INTO `og_admin` (`username`, `password`, `nickname`, `permissions`, `status`) VALUES
('test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '测试管理员', '{"users":["list"],"articles":["list","custom"]}', 1);
-- 测试管理员权限：用户管理（查看、新增、编辑），文章管理（全部权限）

-- ==========================================
-- 用户表
-- ==========================================
DROP TABLE IF EXISTS `og_users`;
CREATE TABLE `og_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `gender` tinyint(1) DEFAULT '0' COMMENT '性别：0未知 1男 2女',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `expired_at` int(11) DEFAULT '0' COMMENT '过期时间（Unix时间戳）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 插入测试数据（密码都是 password）
INSERT INTO `og_users` (`username`, `password`, `nickname`, `email`, `phone`, `status`) VALUES
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '张三', 'zhangsan@example.com', '13800138001', 1),
('user2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '李四', 'lisi@example.com', '13800138002', 1),
('user3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '王五', 'wangwu@example.com', '13800138003', 0);

-- ==========================================
-- 示例：文章表（展示完整字段类型）
-- ==========================================
DROP TABLE IF EXISTS `og_articles`;
CREATE TABLE `og_articles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `author` int(11) unsigned NOT NULL COMMENT '作者ID（关联用户表）',
  `content` text COMMENT '内容（富文本）',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `publish_date` date DEFAULT NULL COMMENT '发布日期',
  `is_published` tinyint(1) DEFAULT '1' COMMENT '是否发布：1发布 0草稿',
  `views` int(11) DEFAULT '0' COMMENT '浏览次数',
  `author_id` int(11) unsigned DEFAULT NULL COMMENT '作者ID（关联管理员）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `is_published` (`is_published`),
  KEY `publish_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- 插入示例文章数据（包含完整字段）
INSERT INTO `og_articles` (`title`, `author`, `content`, `cover`, `publish_date`, `is_published`, `views`, `created_at`) VALUES
('欢迎使用 Flight Base 框架', 
3,
'<h1>Flight Base 框架</h1><p>Flight Base 是一个基于 <strong>Flight + Medoo + Layui</strong> 的超轻量级 PHP 框架。</p><h2>主要特点：</h2><ul><li>✅ 超级轻量：核心代码不到 1000 行</li><li>✅ AI 友好：结构清晰，易于理解和维护</li><li>✅ 无需打包：使用 Layui，修改代码即刻生效</li><li>✅ 快速开发：适合快速开发 API 和管理后台</li><li>✅ 结构清晰：业务逻辑分离，便于扩展</li></ul><p>你可以使用这个框架快速开发各种 Web 应用，包括 API 接口、管理后台和前端页面。</p>', 
NULL, 
'2026-01-23', 
1, 
156, 
NOW()),

('如何使用 Flight 框架开发 API', 
1,
'<h2>Flight PHP 微框架</h2><p>Flight 是一个快速、简单、可扩展的 PHP 微框架。</p><h3>基本用法：</h3><h4>1. 路由定义</h4><pre><code>Flight::route(''GET /api/users'', function(){
    // 返回用户列表
});</code></pre><h4>2. 获取参数</h4><pre><code>$id = Flight::request()->query[''id''];
$name = Flight::request()->data[''name''];</code></pre><h4>3. 返回 JSON</h4><pre><code>Flight::json([''code'' => 0, ''data'' => $users]);</code></pre><h4>4. 中间件</h4><pre><code>Flight::before(''start'', function(){
    // 在所有路由前执行
});</code></pre><p>更多用法请参考官方文档：<a href="https://flightphp.com/" target="_blank">https://flightphp.com/</a></p>', 
NULL,
'2026-01-22', 
1, 
89, 
NOW()),

('Medoo 数据库操作简明教程', 
2,
'<h2>Medoo 轻量级 ORM</h2><p>Medoo 是一个轻量级的 PHP 数据库框架，提供了简洁的 API。</p><h3>常用操作：</h3><h4>查询</h4><pre><code>$users = $db->select(''users'', ''*'', [''status'' => 1]);</code></pre><h4>查询单条</h4><pre><code>$user = $db->get(''users'', ''*'', [''id'' => 1]);</code></pre><h4>插入</h4><pre><code>$db->insert(''users'', [
    ''name'' => ''John'', 
    ''email'' => ''john@example.com''
]);</code></pre><h4>更新</h4><pre><code>$db->update(''users'', [''status'' => 0], [''id'' => 1]);</code></pre><h4>删除</h4><pre><code>$db->delete(''users'', [''id'' => 1]);</code></pre><h4>统计</h4><pre><code>$count = $db->count(''users'', [''status'' => 1]);</code></pre><p>Medoo 支持 MySQL、PostgreSQL、SQLite 等多种数据库。</p>', 
NULL,
'2026-01-21', 
1, 
234, 
NOW()),

('Layui 前端框架使用指南', 
3,
'<h2>Layui - 经典模块化前端框架</h2><p>Layui 是一款经典的模块化前端 UI 框架，<strong>无需 npm 打包，开箱即用</strong>。</p><h3>主要特点：</h3><ol><li>无需构建：直接引入 JS/CSS 即可使用</li><li>模块化：按需加载，提高性能</li><li>轻量级：压缩后仅 200KB</li><li>丰富组件：表格、表单、弹层等</li><li>友好文档：中文文档详尽</li></ol><h3>基本用法：</h3><h4>引入 Layui</h4><pre><code>&lt;script src="layui/layui.js"&gt;&lt;/script&gt;</code></pre><h4>使用模块</h4><pre><code>layui.use([''layer'', ''table''], function(){
    var layer = layui.layer;
    var table = layui.table;
    // ... 业务逻辑
});</code></pre><p>非常适合快速开发管理后台！</p>', 
NULL,
'2026-01-20', 
1, 
178, 
NOW()),

('PHP 最佳实践与开发规范', 
1,
'<h2>PHP 最佳实践</h2><p>PHP 是世界上<em>最好的语言之一</em>，掌握最佳实践能让你的代码更优雅。</p><h3>核心原则：</h3><ol><li><strong>使用 Composer 管理依赖</strong> - 标准化依赖管理</li><li><strong>遵循 PSR 编码规范</strong> - 提高代码可读性</li><li><strong>使用命名空间组织代码</strong> - 避免命名冲突</li><li><strong>合理使用设计模式</strong> - 提高代码复用性</li><li><strong>注意安全问题</strong> - SQL 注入、XSS、CSRF</li><li><strong>使用类型声明</strong> - 提高代码健壮性</li><li><strong>编写单元测试</strong> - 保证代码质量</li><li><strong>使用错误处理和日志</strong> - 便于排查问题</li><li><strong>优化性能</strong> - 缓存、索引、CDN</li><li><strong>代码审查和重构</strong> - 持续改进</li></ol><blockquote><p>好的代码不仅能运行，还要易于维护和扩展。</p></blockquote>', 
NULL,
'2026-01-19', 
1, 
312, 
NOW()),

('配置驱动开发：让 AI 帮你写代码', 
2,
'<h2>配置驱动开发模式</h2><p>在 Flight Base 2.0 中，我们引入了<strong>配置驱动</strong>的开发模式，让开发更加高效。</p><h3>什么是配置驱动？</h3><p>只需定义配置，前端自动生成完整的 CRUD 页面，包括：</p><ul><li>✅ 表格（分页、排序、搜索）</li><li>✅ 表单（新增、编辑、验证）</li><li>✅ 弹窗（宽度自适应）</li><li>✅ 按钮（删除、批量操作）</li></ul><h3>核心优势：</h3><ol><li><strong>代码量减少 80%</strong> - 不再手写重复代码</li><li><strong>AI 极度友好</strong> - 结构化配置，AI 轻松生成</li><li><strong>维护成本低</strong> - 修改配置即可，无需改前端代码</li></ol><h3>示例配置：</h3><pre><code>return [
    ''table'' => [ /* 表格配置 */ ],
    ''form'' => [ /* 表单配置 */ ],
    ''api'' => [ /* 接口配置 */ ]
];</code></pre><p>一次配置，终身受益！🚀</p>',
NULL,
'2026-01-23',
1,
67,
NOW());

-- ==========================================
-- 完成
-- ==========================================
