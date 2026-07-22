<?php
namespace App\api\admin;

use Flight;
use App\middleware\AuthMiddleware;

/**
 * 字典类型管理控制器
 *
 * 字典类型是"字典数据"的分类容器，例如：
 * - user_status（用户状态）
 * - gender（性别）
 *
 * `type` 编码是业务代码中引用字典的唯一标识（见 app/helpers/dict.php），
 * 创建后不允许修改，避免已引用该编码的代码/字典数据失效。
 */
class DictTypeController
{
    /**
     * 字典类型列表
     */
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'list');

        $db = db();

        $page    = (int)getQuery('page', 1);
        $limit   = (int)getQuery('limit', 10);
        $keyword = getQuery('keyword', '');

        $where = [];
        if ($keyword) {
            $where['OR'] = [
                'name[~]' => $keyword,
                'type[~]' => $keyword,
            ];
        }

        $count = $db->count('dict_type', $where);

        $where['LIMIT']  = [($page - 1) * $limit, $limit];
        $where['ORDER']  = ['id' => 'ASC'];

        $list = $db->select('dict_type', '*', $where);

        layuiTable($list, $count);
    }

    /**
     * 不分页获取全部启用的字典类型（供其他模块下拉选择字典类型使用）
     * GET /api/admin/dict/types/options
     */
    public static function options()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'list');

        $db = db();
        $list = $db->select('dict_type', ['id', 'name', 'type'], [
            'status' => 1,
            'ORDER'  => ['id' => 'ASC'],
        ]);

        success($list);
    }

    /**
     * 创建字典类型
     */
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'create');

        $db = db();

        $name = cleanInput(getPost('name'));
        $type = trim((string)getPost('type'));
        $status = (int)getPost('status', 1);
        $remark = cleanInput(getPost('remark', ''));

        if (empty($name)) { error('字典名称不能为空'); return; }
        if (empty($type)) { error('字典类型编码不能为空'); return; }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $type)) {
            error('字典类型编码只能以字母开头，包含字母、数字、下划线');
            return;
        }

        if ($db->has('dict_type', ['type' => $type])) {
            error('该字典类型编码已存在');
            return;
        }

        $result = $db->insert('dict_type', [
            'name'       => $name,
            'type'       => $type,
            'status'     => $status,
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result->rowCount() > 0) {
            auditLog('创建字典类型：' . $name . '(' . $type . ')');
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }

    /**
     * 更新字典类型
     * 注意：type 编码创建后不可修改
     */
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'update');

        $db = db();
        $row = $db->get('dict_type', '*', ['id' => $id]);
        if (!$row) { error('字典类型不存在', 404); return; }

        $data = [];

        $name = getPost('name');
        if ($name !== null && $name !== '') $data['name'] = cleanInput($name);

        $status = getPost('status');
        if ($status !== null) $data['status'] = (int)$status;

        $remark = getPost('remark');
        if ($remark !== null) $data['remark'] = cleanInput($remark);

        if (empty($data)) { error('没有需要更新的内容'); return; }

        $db->update('dict_type', $data, ['id' => $id]);
        auditLog('更新字典类型 ID: ' . $id);
        success([], '更新成功');
    }

    /**
     * 删除字典类型（级联删除该类型下的所有字典数据）
     */
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'delete');

        $db = db();
        $row = $db->get('dict_type', ['id', 'name', 'type'], ['id' => $id]);
        if (!$row) { error('字典类型不存在', 404); return; }

        $db->delete('dict_type', ['id' => $id]);
        $removed = $db->delete('dict_data', ['type' => $row['type']]);

        auditLog('删除字典类型：' . $row['name'] . '(' . $row['type'] . ')，同时删除字典数据 ' . $removed->rowCount() . ' 条');
        success([], '删除成功');
    }
}
