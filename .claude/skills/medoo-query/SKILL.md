---
name: medoo-query
description: Medoo ORM query patterns for Flight Base — correct syntax for JOINs, WHERE modifiers, aggregates, AND/OR nesting, transactions. Reference BEFORE writing any non-trivial Medoo query to avoid syntax errors.
---

# Medoo ORM Query Skill

Medoo v2.2.0 is the ORM for this project. Its array-based DSL is powerful but easy to get wrong. **Reference this skill before writing any JOIN, aggregate, or complex WHERE query.**

Get the DB instance: `$db = db();`

---

## Basic CRUD (Safe Patterns)

```php
$db = db();

// SELECT all
$rows = $db->select('articles', '*', ['status' => 1, 'LIMIT' => 10]);

// SELECT specific columns
$rows = $db->select('articles', ['id', 'title', 'created_at'], ['status' => 1]);

// Single row
$row = $db->get('articles', '*', ['id' => $id]);

// Count
$count = $db->count('articles', ['status' => 1]);

// Check existence
$exists = $db->has('articles', ['title' => $title]);

// INSERT
$db->insert('articles', ['title' => 'Hello', 'status' => 1]);
$newId = $db->id();

// UPDATE
$db->update('articles', ['status' => 0], ['id' => $id]);

// DELETE
$db->delete('articles', ['id' => $id]);
```

---

## WHERE Condition Modifiers (Most Error-Prone)

Medoo uses bracket suffixes on column names. The modifier goes **inside the column name string**, not as a separate parameter.

### Comparison operators

```php
['id[>]' => 10]          // id > 10
['id[>=]' => 10]         // id >= 10
['id[<]' => 100]         // id < 100
['id[<=]' => 100]        // id <= 100
['id[!]' => 5]           // id != 5
['id[<>]' => [1, 100]]   // id BETWEEN 1 AND 100
['id[><]' => [1, 100]]   // id NOT BETWEEN 1 AND 100
```

### LIKE

```php
['name[~]' => 'keyword']     // name LIKE '%keyword%'  (contains)
['name[~]' => '%keyword']    // name LIKE '%keyword'   (ends with — YOU control %)
['name[~]' => 'keyword%']    // name LIKE 'keyword%'   (starts with)
['name[!~]' => 'spam']       // name NOT LIKE '%spam%'
```

**Critical rule:** `[~]` does NOT auto-wrap with `%`. If you want `%keyword%`, you must write it explicitly:
```php
// ✅ Correct — explicit % for contains match
['name[~]' => '%' . $keyword . '%']

// ❌ Wrong — no % means exact LIKE match, which is pointless
['name[~]' => $keyword]
```

### IN / NOT IN

```php
['id' => [1, 2, 3]]          // id IN (1, 2, 3)
['id[!]' => [1, 2, 3]]       // id NOT IN (1, 2, 3)
```

### NULL checks

```php
['deleted_at IS NULL']       // raw condition (no value needed)
['deleted_at IS NOT NULL']   // raw condition
// Or use Medoo's built-in:
$db->select('table', '*', ['deleted_at' => null]);       // IS NULL
$db->select('table', '*', ['deleted_at[!]' => null]);    // IS NOT NULL
```

---

## JOIN Syntax (Second Most Error-Prone)

The JOIN marker uses arrow brackets. The side the arrow points to is the **joined table**.

```php
'[>]table'    => [...]    // LEFT JOIN  (arrow points to joined table → "include all from left")
'[<]table'    => [...]    // RIGHT JOIN
'[><]table'   => [...]    // INNER JOIN
'[<>]table'   => [...]    // FULL JOIN
```

### Single JOIN

```php
$rows = $db->select('articles', [
    '[>]users' => ['author_id' => 'id']     // LEFT JOIN users ON articles.author_id = users.id
], [
    'articles.id',
    'articles.title',
    'users.nickname(author_name)'           // Alias: users.nickname AS author_name
], [
    'articles.status' => 1,
    'ORDER' => ['articles.id' => 'DESC']
]);
```

### Multiple JOINs (array of join tables)

```php
$rows = $db->select('articles', [
    '[>]users'      => ['author_id' => 'id'],
    '[>]categories' => ['category_id' => 'id'],
], [
    'articles.id',
    'articles.title',
    'users.nickname(author_name)',
    'categories.name(category_name)',
]);
```

### JOIN with multiple ON conditions

```php
$rows = $db->select('orders', [
    '[>]products' => [
        'product_id' => 'id',
        'status'     => 1          // Extra ON condition: AND products.status = 1
    ]
], '*');
```

---

## AND / OR Nesting

Medoo uses nested arrays for logical grouping:

```php
// WHERE (status = 1) AND (title LIKE '%keyword%' OR content LIKE '%keyword%')
$where = [
    'status' => 1,
    'OR' => [
        'title[~]'   => '%keyword%',
        'content[~]' => '%keyword%',
    ]
];

// Complex: (a AND b) OR (c AND d)
$where = [
    'OR #1' => ['status' => 1, 'type' => 'article'],   // Group 1 (ANDed internally)
    'OR #2' => ['status' => 1, 'type' => 'page'],       // Group 2 (ANDed internally)
];
// The #1 / #2 markers distinguish the OR groups
```

**AND is the default** — all top-level keys are ANDed together. **OR must be explicit** and contains its own sub-array.

### Common mistake: forgetting to wrap OR

```php
// ❌ Wrong — 'OR' is not a real column
['status' => 1, 'OR' => 'something']

// ✅ Correct — OR value must be an array of conditions
['status' => 1, 'OR' => ['title[~]' => '%a%', 'content[~]' => '%a%']]
```

---

## ORDER, LIMIT, GROUP BY

These go **inside the WHERE array** as special uppercase keys:

```php
$where = [
    'status' => 1,
    'ORDER'  => ['id' => 'DESC'],         // or 'created_at DESC'
    'LIMIT'  => [0, 10],                  // [offset, count]
    'GROUP'  => 'category_id',
];
$rows = $db->select('articles', '*', $where);
```

### ORDER
```php
'ORDER' => ['id' => 'DESC']               // ORDER BY id DESC
'ORDER' => ['created_at' => 'ASC']        // ORDER BY created_at ASC
'ORDER' => ['category_id', 'id' => 'DESC'] // ORDER BY category_id, id DESC
```

### LIMIT
```php
'LIMIT' => 10              // LIMIT 10
'LIMIT' => [5, 10]         // LIMIT 5, 10  (offset 5, count 10)
```

### GROUP BY
```php
'GROUP' => 'category_id'                              // GROUP BY category_id
'GROUP' => ['category_id', 'status']                  // GROUP BY category_id, status
```

### HAVING
```php
'HAVING' => ['COUNT(id)(count)' => ['>', 5]]          // HAVING COUNT(id) > 5
```

---

## Aggregate Functions

### COUNT, SUM, AVG, MAX, MIN

```php
$stats = $db->select('articles', [
    'COUNT(id)(total)',                     // COUNT(id) AS total
    'SUM(views)(total_views)',             // SUM(views) AS total_views
    'AVG(views)(avg_views)',               // AVG(views) AS avg_views
    'MAX(views)(max_views)',               // MAX(views) AS max_views
    'MIN(views)(min_views)',               // MIN(views) AS min_views
], ['status' => 1]);
```

### With GROUP BY

```php
$stats = $db->select('articles', [
    'category_id',
    'COUNT(id)(count)',
    'AVG(views)(avg)',
], [
    'status' => 1,
    'GROUP'  => 'category_id',
    'ORDER'  => ['count' => 'DESC'],
]);
```

### Raw aggregate in SELECT

```php
// When the column itself is dynamic:
$db->select('articles', [
    'id',
    'title',
    'LENGTH(content)(content_length)',     // LENGTH(content) AS content_length
]);
```

---

## Atomic Operations

```php
// Increment
$db->update('articles', ['views[+]' => 1], ['id' => $id]);

// Decrement
$db->update('users', ['points[-]' => 10], ['id' => $userId]);

// Multiply
$db->update('products', ['price[*]' => 1.1], ['id' => $id]);   // 10% price increase
```

---

## Subqueries

```php
// Subquery in WHERE
$authors = $db->select('users', '*', [
    'id' => $db->select('articles', 'author_id', ['status' => 1])
    // WHERE id IN (SELECT author_id FROM articles WHERE status = 1)
]);

// Subquery with comparison
$rows = $db->select('articles', '*', [
    'views[>]' => $db->select('articles', ['views' => Medoo::raw('AVG(<views>)')])
    // WHERE views > (SELECT AVG(views) FROM articles)
]);
```

---

## Transactions

```php
$db->action(function($db) {
    $db->insert('orders', ['user_id' => $userId, 'total' => $total]);
    $orderId = $db->id();

    $db->update('users', ['points[-]' => 10], ['id' => $userId]);

    $db->insert('point_logs', [
        'user_id' => $userId,
        'amount'  => -10,
        'ref_id'  => $orderId
    ]);

    return $orderId;  // value returned from action()
});

// Auto-rollback on any exception thrown inside the closure.
```

---

## Raw Expressions (Medoo::raw)

When Medoo's DSL can't express what you need:

```php
use Medoo\Medoo;

$db->select('articles', [
    'id',
    'title',
    'IFNULL(cover, "/default.jpg")(cover_url)'  // Won't work — need raw
]);

// Instead use Medoo::raw:
$rows = $db->select('articles', [
    'id',
    'title',
    Medoo::raw('IFNULL(<cover>, "/default.jpg") AS <cover_url>')
]);
```

---

## Raw SQL (Last Resort)

```php
$result = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
                       FROM og_articles WHERE status = 1
                       GROUP BY month ORDER BY month DESC")
             ->fetchAll(PDO::FETCH_ASSOC);
```

Use `$db->query()` for SELECT, `$db->exec()` for INSERT/UPDATE/DELETE.

---

## Common Mistakes Checklist

| Mistake | Wrong | Correct |
|---------|-------|---------|
| LIKE without `%` | `['name[~]' => $kw]` | `['name[~]' => '%'.$kw.'%']` |
| JOIN arrow direction | `['[<]users' => ...]` for LEFT | `['[>]users' => ...]` for LEFT |
| OR not as array | `'OR' => 'condition'` | `'OR' => ['col' => 'val']` |
| LIMIT forgetting offset | Want skip 10 take 10, write `'LIMIT' => 10` | `'LIMIT' => [10, 10]` |
| ORDER in wrong place | `$db->select('t', '*', [], ['ORDER' => ...])` | `$db->select('t', '*', ['ORDER' => ...])` (in WHERE array) |
| Aggregate without alias | `'COUNT(id)'` | `'COUNT(id)(total)'` |
| Forgetting `$db->id()` after INSERT | — | `$newId = $db->id();` |
| LIKE with `[~]` thinking auto-`%` | — | Medoo does NOT auto-wrap `%` |
