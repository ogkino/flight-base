<?php
/**
 * 数据字典辅助函数
 *
 * 字典由两张表组成：
 * - og_dict_type：字典类型（分类），如 user_status、gender
 * - og_dict_data ：字典数据（具体的 label/value 项）
 *
 * 业务代码通过 dict($type) / dictLabel($type, $value) 读取，
 * CrudConfig.php 中的 select/radio/checkbox 的 options 也可以直接用 dict() 的返回值拼装，
 * 详见 docs/DICT.md。
 *
 * 缓存说明（Worker 模式安全）：
 * 使用 Flight::set()/Flight::get() 做"请求级"缓存 —— 同一次请求内多次调用同一个
 * $type 不会重复查库；但 Flight 是单例，在 FrankenPHP Worker 模式下 Flight::set()
 * 的值会跨请求持久，因此 worker.php 会在每次请求开始前清空 'dictCache'，
 * 避免上一个请求缓存的字典数据"泄漏"给下一个请求（例如管理员刚编辑了字典后，
 * 下一个请求仍然读到旧数据）。
 * 千万不要在这里改用 PHP 的 static 变量做跨调用缓存，那样在 Worker 模式下
 * 会导致字典数据在进程重启前"永久"不更新。
 */

/**
 * 获取指定字典类型下所有启用的字典项，按 sort 升序排列
 *
 * @param string $type 字典类型编码，如 'user_status'
 * @return array<int, array{id:int,type:string,label:string,value:string,sort:int,color:?string,is_default:int,remark:?string}>
 */
function dict(string $type): array
{
    $cache = Flight::get('dictCache');
    if (!is_array($cache)) {
        $cache = [];
    }

    if (isset($cache[$type])) {
        return $cache[$type];
    }

    $rows = db()->select('dict_data', [
        'id', 'type', 'label', 'value', 'sort', 'color', 'is_default', 'remark',
    ], [
        'type'   => $type,
        'status' => 1,
        'ORDER'  => ['sort' => 'ASC', 'id' => 'ASC'],
    ]);

    $cache[$type] = $rows;
    Flight::set('dictCache', $cache);

    return $rows;
}

/**
 * 根据字典类型 + 键值反查显示文本（label）
 *
 * @param string $type    字典类型编码
 * @param mixed  $value   字典键值（与 dict_data.value 按字符串比较）
 * @param string $default 未找到时的默认返回值
 */
function dictLabel(string $type, $value, string $default = ''): string
{
    foreach (dict($type) as $item) {
        if ((string)$item['value'] === (string)$value) {
            return $item['label'];
        }
    }
    return $default;
}

/**
 * 获取指定字典类型的默认项（is_default = 1），未设置默认项时返回 null
 */
function dictDefault(string $type): ?array
{
    foreach (dict($type) as $item) {
        if ((int)$item['is_default'] === 1) {
            return $item;
        }
    }
    return null;
}
