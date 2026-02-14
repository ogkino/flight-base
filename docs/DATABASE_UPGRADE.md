# 数据库升级说明

> **版本：v2.1.3**  
> **日期：2026-01-23**

## ⚠️ 重要提示

本次更新对 `og_articles` 表进行了字段扩展，**需要重新导入数据库**。

**注意**：重新导入会清空现有数据，请提前备份！

---

## 📊 数据库变更

### og_articles 表

#### 新增字段

| 字段名 | 类型 | 说明 | 默认值 |
|--------|------|------|--------|
| `cover` | VARCHAR(255) | 封面图路径 | NULL |
| `publish_date` | DATE | 发布日期 | NULL |
| `is_published` | TINYINT(1) | 是否发布 | 1 |

#### 字段调整

- **重命名**：`status` → `is_published`（语义更清晰）
- **调整顺序**：按照业务逻辑重新排列字段

#### 完整表结构

```sql
CREATE TABLE `og_articles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `author` varchar(50) NOT NULL COMMENT '作者',
  `content` text COMMENT '内容（富文本）',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `publish_date` date DEFAULT NULL COMMENT '发布日期',
  `is_published` tinyint(1) DEFAULT '1' COMMENT '是否发布：1发布 0草稿',
  `views` int(11) DEFAULT '0' COMMENT '浏览次数',
  `author_id` int(11) unsigned DEFAULT NULL COMMENT '作者ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `is_published` (`is_published`),
  KEY `publish_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';
```

---

## 🔄 升级步骤

### 方式一：全新安装（推荐）

**适用场景**：测试环境、新项目、数据不重要

#### 1. 备份数据（可选）

如果有重要数据，先备份：

```sql
-- 备份整个数据库
mysqldump -u root -p flight_base > backup_flight_base.sql

-- 或只备份数据（不包含表结构）
mysqldump -u root -p flight_base --no-create-info > backup_data.sql
```

#### 2. 重新导入

**方式 A：使用 phpMyAdmin**

1. 打开 phpMyAdmin
2. 选择 `flight_base` 数据库
3. 点击"导入"标签
4. 选择 `database.sql` 文件
5. 点击"执行"

**方式 B：使用命令行**

```bash
# 删除旧数据库（可选）
mysql -u root -p -e "DROP DATABASE IF EXISTS flight_base;"

# 导入新数据库
mysql -u root -p < database.sql
```

**方式 C：使用 phpstudy**

1. 打开 phpstudy → 数据库管理
2. 选择 `flight_base` 数据库
3. 点击"导入 SQL"
4. 选择 `database.sql` 文件
5. 执行导入

#### 3. 验证

访问后台，查看文章列表：

```
http://your-domain.com/admin/
→ 内容管理 → 文章管理
```

**期望结果**：
- ✅ 表格显示 6 篇示例文章
- ✅ 编辑时可以看到封面图、发布日期、是否发布等字段

---

### 方式二：保留数据升级（手动）

**适用场景**：生产环境、有重要数据

#### 1. 备份数据

```sql
-- 导出现有文章数据
SELECT * FROM og_articles INTO OUTFILE '/tmp/articles_backup.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

#### 2. 添加新字段

```sql
USE flight_base;

-- 添加 cover 字段
ALTER TABLE `og_articles` 
ADD COLUMN `cover` VARCHAR(255) DEFAULT NULL COMMENT '封面图' 
AFTER `content`;

-- 添加 publish_date 字段
ALTER TABLE `og_articles` 
ADD COLUMN `publish_date` DATE DEFAULT NULL COMMENT '发布日期' 
AFTER `cover`;

-- 重命名 status 为 is_published（如果表结构不同，可能不需要）
ALTER TABLE `og_articles` 
CHANGE COLUMN `status` `is_published` TINYINT(1) DEFAULT '1' COMMENT '是否发布：1发布 0草稿';

-- 添加索引
ALTER TABLE `og_articles` ADD KEY `is_published` (`is_published`);
ALTER TABLE `og_articles` ADD KEY `publish_date` (`publish_date`);
```

#### 3. 初始化新字段

```sql
-- 为已有文章设置默认发布日期
UPDATE og_articles 
SET publish_date = DATE(created_at) 
WHERE publish_date IS NULL;
```

#### 4. 验证

```sql
-- 检查表结构
DESCRIBE og_articles;

-- 查看数据
SELECT id, title, cover, publish_date, is_published FROM og_articles LIMIT 10;
```

---

## 🧪 测试数据

新的 `database.sql` 包含 6 篇示例文章：

1. **欢迎使用 Flight Base 框架** - 框架介绍
2. **如何使用 Flight 框架开发 API** - Flight 教程
3. **Medoo 数据库操作简明教程** - Medoo 教程
4. **Layui 前端框架使用指南** - Layui 教程
5. **PHP 最佳实践与开发规范** - PHP 最佳实践
6. **配置驱动开发：让 AI 帮你写代码** - 配置驱动介绍

**特点**：
- ✅ 使用富文本 HTML 格式
- ✅ 包含标题、段落、列表、代码块等
- ✅ 包含发布日期、浏览量等完整数据
- ✅ 适合演示和测试

---

## ❓ 常见问题

### 1. 导入失败：表已存在

**错误信息**：`Table 'og_articles' already exists`

**解决方案**：`database.sql` 中已包含 `DROP TABLE IF EXISTS` 语句，如果还报错，手动删除：

```sql
DROP TABLE IF EXISTS `og_articles`;
```

然后重新导入。

---

### 2. 字段缺失

**症状**：表单提交时报错"Unknown column 'cover'"

**原因**：表结构未更新

**解决方案**：重新导入 `database.sql` 或手动执行字段添加 SQL。

---

### 3. 数据丢失

**原因**：重新导入会清空数据

**解决方案**：
- 提前备份（见"方式二：保留数据升级"）
- 或使用手动添加字段的方式（不会删除数据）

---

## 📚 相关文档

- [README.md](../README.md) - 快速开始
- [CHANGELOG.md](CHANGELOG.md) - 版本更新日志
- [ADMIN_DEV.md](ADMIN_DEV.md) - 后台开发指南

---

**升级完成后，请访问后台测试新功能！** 🚀
