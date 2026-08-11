<?php
/**
 * 字典数据管理弹层页面
 *
 * 访问路径：GET /admin/dict-data-manage?type=xxx
 * 打开方式：「字典管理」（dictType）列表的"配置数据"行操作，以 layer iframe 弹层方式打开
 * 加载方式：由 DictDataController::managePage() 以 include 方式加载，因此这里可以直接使用：
 *   $dictType  当前字典类型信息（数组，包含 id/name/type/status/remark）
 *   $type      当前字典类型编码（字符串，同 $dictType['type']）
 *
 * 页面内的增删改均调用标准的 /api/admin/dict/data/* 接口（见 DictDataController），
 * 权限校验逐项走 checkPermission('dictType', 'list'|'create'|'update'|'delete')
 * （字典数据复用 dictType 权限维度，不再单独存在 dictData 权限）。
 *
 * @var array $dictType 由 DictDataController::managePage() include 时注入
 */

$pageTitle = lang('view.dict_data_title') . ' - ' . $dictType['name'];
include __DIR__ . '/_head.php';
?>

<div class="view-container" style="padding: 16px;">

    <!-- 顶部：所属字典信息 -->
    <div class="card" style="margin-bottom: 14px; padding: 12px 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div>
                <span class="view-meta-title">
                    <i class="layui-icon layui-icon-template"></i>
                    <?= htmlspecialchars($dictType['name']) ?>
                </span>
                <span class="view-meta-tag"><?= htmlspecialchars($dictType['type']) ?></span>
                <?php if (!empty($dictType['remark'])): ?>
                    <span class="view-meta-desc"><?= htmlspecialchars($dictType['remark']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <input type="text" id="keyword" placeholder="<?= htmlspecialchars(lang('view.dict_data_search_ph')) ?>" class="layui-input" style="width:180px; display:inline-block; height:32px;">
                <button class="layui-btn layui-btn-sm" onclick="searchData()"><i class="layui-icon layui-icon-search"></i> <?= htmlspecialchars(lang('view.search')) ?></button>
                <button class="layui-btn layui-btn-sm layui-btn-normal" onclick="openForm()"><i class="layui-icon layui-icon-add-1"></i> <?= htmlspecialchars(lang('view.dict_data_add_btn')) ?></button>
            </div>
        </div>
    </div>

    <!-- 数据表格 -->
    <table id="dictDataTable" lay-filter="dictDataTable"></table>

</div>

<!-- 新增/编辑 表单模板（隐藏，由 layer.open 弹出展示） -->
<div id="dictDataFormTpl" style="display:none; padding: 20px 20px 0;">
    <form class="layui-form" id="dictDataForm" lay-filter="dictDataForm">
        <input type="hidden" name="id">
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.dict_data_label')) ?></label>
            <div class="layui-input-block">
                <input type="text" name="label" placeholder="<?= htmlspecialchars(lang('view.dict_data_label_ph')) ?>" class="layui-input" lay-verify="required">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.dict_data_value')) ?></label>
            <div class="layui-input-block">
                <input type="text" name="value" placeholder="<?= htmlspecialchars(lang('view.dict_data_value_ph')) ?>" class="layui-input" lay-verify="required">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.sort')) ?></label>
            <div class="layui-input-block">
                <input type="number" name="sort" value="0" placeholder="<?= htmlspecialchars(lang('view.dict_data_sort_ph')) ?>" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.dict_data_color')) ?></label>
            <div class="layui-input-block" style="display:flex; align-items:center; gap:8px;">
                <input type="color" name="color" id="colorPicker" value="#1E9FFF" style="width:40px; height:32px; padding:2px; border:1px solid #e6e6e6; border-radius:4px;">
                <input type="text" name="color_text" id="colorText" placeholder="<?= htmlspecialchars(lang('view.dict_data_color_ph')) ?>" class="layui-input" style="flex:1;">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.dict_data_is_default')) ?></label>
            <div class="layui-input-block">
                <input type="checkbox" name="is_default" lay-skin="switch" lay-text="<?= htmlspecialchars(lang('view.dict_data_yes_no')) ?>">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.status')) ?></label>
            <div class="layui-input-block">
                <input type="checkbox" name="status" lay-skin="switch" lay-text="<?= htmlspecialchars(lang('view.dict_data_status_switch')) ?>" checked>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= htmlspecialchars(lang('view.remark')) ?></label>
            <div class="layui-input-block">
                <textarea name="remark" placeholder="<?= htmlspecialchars(lang('view.optional')) ?>" class="layui-textarea"></textarea>
            </div>
        </div>
        <div class="layui-form-item" style="text-align:right; margin-top:24px;">
            <button type="button" class="layui-btn layui-btn-primary" onclick="layer.closeAll();"><?= htmlspecialchars(lang('view.cancel')) ?></button>
            <button type="button" class="layui-btn" lay-submit lay-filter="dictDataForm"><?= htmlspecialchars(lang('view.confirm')) ?></button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/_foot.php'; ?>

<script>
const DICT_TYPE = <?= json_encode($dictType['type']) ?>;
let table, form, layer;
let editingId = null;

layui.use(['table', 'form', 'layer'], function () {
    table = layui.table;
    form = layui.form;
    layer = layui.layer;

    renderTable();

    // 颜色选择器与文本框双向同步
    document.getElementById('colorPicker').addEventListener('input', function (e) {
        document.getElementById('colorText').value = e.target.value;
    });
    document.getElementById('colorText').addEventListener('input', function (e) {
        if (/^#[0-9a-fA-F]{6}$/.test(e.target.value)) {
            document.getElementById('colorPicker').value = e.target.value;
        }
    });

    form.on('submit(dictDataForm)', function (data) {
        submitForm(data.field);
        return false;
    });

    table.on('tool(dictDataTable)', function (obj) {
        if (obj.event === 'edit') {
            openForm(obj.data);
        } else if (obj.event === 'delete') {
            confirmDelete(obj);
        }
    });
});

function renderTable(keyword) {
    table.render({
        elem: '#dictDataTable',
        url: '/api/admin/dict/data',
        method: 'GET',
        headers: { Authorization: getToken() },
        where: { type: DICT_TYPE, keyword: keyword || '' },
        page: true,
        limit: 20,
        cols: [[
            { field: 'id', title: 'ID', width: 70, sort: true },
            { field: 'label', title: vt('dict_data_col_label', '字典标签'), minWidth: 140 },
            { field: 'value', title: vt('dict_data_col_value', '字典键值'), width: 120 },
            { field: 'sort', title: vt('sort', '排序'), width: 80, sort: true },
            {
                field: 'color', title: vt('dict_data_col_color', '颜色'), width: 105, templet: function (d) {
                    if (!d.color) return '<span style="color:#ccc;">-</span>';
                    return '<span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:' + d.color + ';vertical-align:middle;margin-right:4px;"></span>' + d.color;
                }
            },
            {
                field: 'is_default', title: vt('dict_data_col_default', '默认项'), width: 90, templet: function (d) {
                    return d.is_default == 1
                        ? '<span class="layui-badge layui-bg-blue">' + vt('yes', '是') + '</span>'
                        : '<span class="layui-badge layui-bg-gray">' + vt('no', '否') + '</span>';
                }
            },
            {
                field: 'status', title: vt('status', '状态'), width: 100, templet: function (d) {
                    return d.status == 1
                        ? '<span class="layui-badge layui-bg-green">' + vt('enabled', '启用') + '</span>'
                        : '<span class="layui-badge layui-bg-gray">' + vt('disabled_status', '禁用') + '</span>';
                }
            },
            { field: 'remark', title: vt('remark', '备注'), minWidth: 120 },
            { title: vt('actions', '操作'), width: 190, toolbar: '#dictDataRowActions', fixed: 'right' },
        ]],
    });
}

function searchData() {
    renderTable(document.getElementById('keyword').value.trim());
}

function openForm(row) {
    editingId = row ? row.id : null;

    layer.open({
        type: 1,
        title: editingId ? vt('dict_data_edit_title', '编辑字典数据') : vt('dict_data_add_title', '新增字典数据'),
        area: ['520px', '620px'],
        content: $('#dictDataFormTpl'),
        success: function () {
            const f = document.getElementById('dictDataForm');
            f.id.value = row ? row.id : '';
            f.label.value = row ? row.label : '';
            f.value.value = row ? row.value : '';
            f.sort.value = row ? row.sort : 0;
            f.remark.value = row ? (row.remark || '') : '';

            const color = row && row.color ? row.color : '#1E9FFF';
            document.getElementById('colorPicker').value = color;
            document.getElementById('colorText').value = row ? (row.color || '') : '';

            f.is_default.checked = !!(row && row.is_default == 1);
            f.status.checked = !row || row.status != 0;
            form.render('checkbox');
        }
    });
}

function submitForm(field) {
    const data = {
        type: DICT_TYPE,
        label: field.label,
        value: field.value,
        sort: field.sort || 0,
        color: field.color_text || '',
        is_default: field.is_default ? 1 : 0,
        status: field.status ? 1 : 0,
        remark: field.remark || '',
    };

    const isEdit = !!field.id;
    const url = isEdit ? '/api/admin/dict/data/' + field.id : '/api/admin/dict/data';

    request(url, { method: 'POST', data: data }).then(function (res) {
        if (res.code === 0) {
            successMsg(isEdit ? vt('update_success', '更新成功') : vt('create_success', '创建成功'));
            layer.closeAll();
            renderTable(document.getElementById('keyword').value.trim());
        } else {
            errorMsg(res.msg || vt('op_failed', '操作失败'));
        }
    });
}

function confirmDelete(obj) {
    layer.confirm(vt('dict_data_delete_confirm', '确定删除字典数据「:name」吗？').replace(':name', obj.data.label), { icon: 3, title: vt('tip', '提示') }, function (index) {
        request('/api/admin/dict/data/' + obj.data.id, { method: 'DELETE' }).then(function (res) {
            if (res.code === 0) {
                successMsg(vt('delete_success', '删除成功'));
                obj.del();
            } else {
                errorMsg(res.msg || vt('op_failed', '删除失败'));
            }
        });
        layer.close(index);
    });
}
</script>

<script type="text/html" id="dictDataRowActions">
    <a class="layui-btn layui-btn-xs layui-btn-primary" lay-event="edit"><i class="layui-icon layui-icon-edit"></i> <?= htmlspecialchars(lang('view.edit')) ?></a>
    <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="delete"><i class="layui-icon layui-icon-delete"></i> <?= htmlspecialchars(lang('view.delete')) ?></a>
</script>

</body>
</html>
