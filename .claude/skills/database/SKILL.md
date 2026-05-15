---
name: database
description: Database operations for Flight Base — table design conventions, CrudConfig-to-SQL type mapping, CREATE TABLE templates, migration file management. Use MCP MySQL tools for all SQL execution.
---

# Database Skill

Use this skill for any database operations in the Flight Base framework: creating tables for new modules, modifying existing tables, generating SQL from CrudConfig definitions.

**This skill works together with `crud-admin` and `api-development` — when creating a new module, use this skill for the database layer.**

---

## MCP Tools Available

All SQL operations go through the MCP MySQL tools:

| Tool | Use |
|------|-----|
| `mcp__mysql__connect_db` | Connect to database (host, user, password, database) |
| `mcp__mysql__list_tables` | List all tables |
| `mcp__mysql__describe_table` | Show table structure (columns, types, keys, defaults) |
| `mcp__mysql__query` | Execute SELECT queries |
| `mcp__mysql__execute` | Execute INSERT / UPDATE / DELETE / DDL statements |

**Workflow:** Always `connect_db` first, then use the appropriate tool.

---

## Table Conventions

Every table in this framework follows these rules:

### Required columns (always include)

```sql
`id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
```

### Optional standard columns

```sql
`status`     TINYINT(1) DEFAULT 1,          -- 1=enabled, 0=disabled
`expired_at` INT(11) DEFAULT 0,             -- Unix timestamp, 0=no expiry
```

### Naming conventions

| Rule | Example |
|------|---------|
| Table prefix | `og_` (from `DB_PREFIX` in `.env`) |
| Column style | `snake_case` |
| Primary key | `id` (always) |
| Foreign keys | `{table}_id` — `user_id`, `article_id` |
| Boolean flags | `is_{adjective}` — `is_published`, `is_active` |
| Loose foreign keys (no FK constraint) | `author INT(11) UNSIGNED` referencing users table |

---

## CrudConfig Field Type → SQL Type Mapping

When creating a table from a CrudConfig `form` definition, use this mapping:

| CrudConfig `type` | SQL Column | Notes |
|-------------------|------------|-------|
| `input` | `VARCHAR(200)` | Default; use `VARCHAR(100)` for short, `VARCHAR(500)` for URLs |
| `password` | `VARCHAR(255)` | bcrypt hash is always 60 chars, 255 for future-proofing |
| `textarea` | `TEXT` | |
| `editor` | `TEXT` | Rich text content |
| `number` | `INT(11)` | Use `DECIMAL(M,D)` for prices |
| `switch` | `TINYINT(1) DEFAULT 1` | 1=ON, 0=OFF |
| `select` | `VARCHAR(100)` | If options are strings; `INT` if referencing another table |
| `radio` | `VARCHAR(50)` | |
| `date` | `DATE` | |
| `datetime` | `DATETIME` | |
| `time` | `TIME` | |
| `timestamp` | `INT(11)` | Unix timestamp (displayed as datetime by renderer) |
| `image` | `VARCHAR(255)` | Stores file path/URL |
| `upload` | `VARCHAR(255)` | Stores file path/URL |
| `color` | `VARCHAR(20)` | Hex color `#RRGGBB` |
| `slider` | `INT(11)` | |
| `permissions` | `TEXT` | JSON string |

### Additional column rules derived from CrudConfig field config

| CrudConfig Option | SQL Result |
|-------------------|------------|
| `'required' => true` | Column: `NOT NULL` |
| `'required' => 'add'` | Column: `NULL` (nullable in DB, validated in PHP) |
| Default not specified | Usually `NULL`; use `DEFAULT ''` for VARCHAR, `DEFAULT 0` for INT |
| Field used for sorting | Add `INDEX` |
| Field used in search (`search.name`) | Add `INDEX` |

---

## CREATE TABLE Template

```sql
CREATE TABLE `og_{table_name}` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(200) NOT NULL,           -- from form: input, required
    `description` TEXT,                            -- from form: textarea
    `price`      DECIMAL(10,2) DEFAULT 0.00,     -- from form: number
    `cover`      VARCHAR(255) DEFAULT '',          -- from form: image
    `status`     TINYINT(1) DEFAULT 1,            -- from form: switch
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),                -- if used in search/filter
    INDEX `idx_created_at` (`created_at`)         -- if list is sorted by this
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## End-to-End Workflow: Create Table for a CRUD Module

### Step 1: Understand the CrudConfig form fields

Read `app/config/CrudConfig.php` → the module's `form` array. Each field has `name`, `type`, and optional constraints (`required`, `required_on_add`, `required_on_edit`).

### Step 2: Map each field to SQL

For example, from the articles config:
```php
// CrudConfig::articles()['form']
['type' => 'input',  'name' => 'title',    'required' => true]   → `title` VARCHAR(200) NOT NULL
['type' => 'select', 'name' => 'author',   'required' => true]   → `author` INT(11) UNSIGNED NOT NULL
['type' => 'editor', 'name' => 'content',  'required' => true]   → `content` TEXT NOT NULL
['type' => 'image',  'name' => 'cover']                          → `cover` VARCHAR(255) DEFAULT ''
['type' => 'date',   'name' => 'publish_date']                   → `publish_date` DATE DEFAULT NULL
['type' => 'switch', 'name' => 'is_published']                   → `is_published` TINYINT(1) DEFAULT 1
```

### Step 3: Execute CREATE TABLE via MCP

```sql
-- First connect:
-- mcp__mysql__connect_db with credentials from .env

-- Then execute:
CREATE TABLE `og_products` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(200) NOT NULL,
    `description` TEXT,
    `price`       DECIMAL(10,2) DEFAULT 0.00,
    `cover`       VARCHAR(255) DEFAULT '',
    `status`      TINYINT(1) DEFAULT 1,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 4: Verify the table

```sql
-- Check structure
DESCRIBE og_products;

-- Insert test data if needed
INSERT INTO `og_products` (`name`, `description`, `price`) VALUES ('Test', 'Test product', 9.99);
SELECT * FROM `og_products`;
```

### Step 5: Save migration SQL file

Save the CREATE TABLE statement to a new `.sql` file for reproducibility:

```
example_db.sql          ← existing (initial schema)
database/
  migrations/
    001_create_products.sql   ← new migration
```

Or append to `example_db.sql` if the project keeps a single schema file.

---

## Common Modification Patterns

### Add a new column to existing table

```sql
ALTER TABLE `og_products`
ADD COLUMN `category_id` INT(11) UNSIGNED DEFAULT 0 AFTER `id`,
ADD INDEX `idx_category_id` (`category_id`);
```

### Add a search index

```sql
ALTER TABLE `og_products`
ADD INDEX `idx_name` (`name`);   -- for search: keyword LIKE search on name
```

### Change column type to match updated CrudConfig

```sql
ALTER TABLE `og_products`
MODIFY COLUMN `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00;
```

### Soft delete pattern (if needed)

```sql
ALTER TABLE `og_products`
ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0,
ADD INDEX `idx_is_deleted` (`is_deleted`);
```

Then in controller queries, always add `'is_deleted' => 0` to WHERE clauses.

---

## Reading .env for Database Credentials

Before any MCP operation, read `.env` in the project root to get connection parameters:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=flight_base
DB_USER=flight
DB_PASS=flight
DB_PREFIX=og_
```

Connect with:
```
mcp__mysql__connect_db(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
```

---

## Safety Guidelines

- **Always describe the table first** before ALTER to understand current structure
- **Test SELECT before UPDATE/DELETE** — wrap the WHERE clause in a SELECT to verify affected rows
- **Use MCP `execute` for DDL** (CREATE, ALTER, DROP) and DML (INSERT, UPDATE, DELETE)
- **Use MCP `query` for SELECT** — it's read-only, no risk
- **Backup mindset**: for destructive changes on production data, ask the user to confirm via dump first
