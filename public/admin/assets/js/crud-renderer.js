/**
 * CRUD 通用渲染器 v2.1
 * 
 * 根据后端配置自动生成表格、表单、搜索等
 * 新增支持：上传、富文本、日期选择器、开关、颜色选择器等
 */

class CrudRenderer {
    constructor(config, containerId) {
        this.config = config;
        this.container = document.getElementById(containerId);
        this.tableIns = null;
        this.uploadFiles = {}; // 存储上传的文件
        this.editors = {}; // 存储富文本编辑器实例
        this.selectMap = {}; // 存储下拉框选项映射 {fieldName: {value: label}}
    }
    
    /**
     * 渲染完整页面
     */
    render() {
        if (this.config.page.type === 'form') {
            // 纯表单页面（如修改密码）
            this.renderFormPage();
        } else {
            // CRUD 页面（默认）
            this.renderCrudPage();
        }
    }
    
    /**
     * 渲染 CRUD 页面
     */
    renderCrudPage() {
        const html = `
            <div class="table-container">
                ${this.renderToolbar()}
                ${this.renderTablePlaceholder()}
            </div>
        `;
        this.container.innerHTML = html;
        
        // 使用 Layui 渲染
        layui.use(['table', 'form', 'layer', 'laydate', 'upload', 'util', 'colorpicker', 'slider', 'rate'], async () => {
            // 优先初始化搜索组件
            this.initSearchComponents();
            this.bindEvents();
            
            // 最后渲染表格（因为它涉及网络请求）
            await this.renderTable();
        });
    }

    /**
     * 初始化搜索组件
     */
    initSearchComponents() {
        const laydate = layui.laydate;
        const form = layui.form;
        const search = this.config.search || [];
        
        search.forEach(field => {
            if (field.type === 'date' || field.type === 'datetime') {
                const id = `#search-date-${field.name}`;
                // 移除可能存在的旧实例
                if ($(id).attr('lay-key')) {
                    return; 
                }
                
                laydate.render({
                    elem: id,
                    type: field.type === 'datetime' ? 'datetime' : 'date',
                    trigger: 'click',
                    done: (value, date) => {
                        // 触发搜索
                        // setTimeout(() => {
                        //     $(`button[lay-filter="search"]`).click();
                        // }, 100);
                    }
                });
            } else if (field.type === 'select' && field.url) {
                // 加载动态下拉数据
                let url = field.url;
                if (!url.startsWith('http') && !url.startsWith('/')) {
                    url = API_BASE + url;
                }
                
                request(url, { method: 'GET' }).then(res => {
                     if (res.code === 0) {
                         const list = res.data.list || res.data;
                         const valueField = field.valueField || 'id';
                         const labelField = field.labelField || 'name';
                         
                         const $select = $(`select[name="${field.name}"]`);
                         if ($select.length > 0) {
                             let html = `<option value="">${field.placeholder || '请选择'}</option>`;
                             if (Array.isArray(list)) {
                                 list.forEach(item => {
                                     html += `<option value="${item[valueField]}">${item[labelField]}</option>`;
                                 });
                             }
                             $select.html(html);
                             form.render('select');
                         }
                     }
                }).catch(err => console.error('加载搜索下拉数据失败', err));
            }
        });
        
        // 渲染静态下拉框
        form.render('select');
    }
    
    /**
     * 渲染工具栏
     */
    renderToolbar() {
        const search = this.config.search || [];
        const toolbar = this.config.toolbar || [];
        
        let searchHtml = '';
        search.forEach(field => {
            if (field.type === 'date' || field.type === 'datetime') {
                searchHtml += `
                    <div class="layui-inline">
                        <input type="text" name="${field.name}" 
                               id="search-date-${field.name}"
                               placeholder="${field.placeholder || '请选择日期'}" 
                               class="layui-input search-date-item" 
                               style="width: ${field.width || 200}px;"
                               autocomplete="off">
                    </div>
                `;
            } else if (field.type === 'select') {
                let optionsHtml = `<option value="">${field.placeholder || '请选择'}</option>`;
                if (field.options && Array.isArray(field.options)) {
                    field.options.forEach(opt => {
                        optionsHtml += `<option value="${opt.value}">${opt.label || opt.title || opt.value}</option>`;
                    });
                }
                
                searchHtml += `
                    <div class="layui-inline" style="width: ${field.width || 200}px;">
                        <select name="${field.name}" lay-search lay-filter="search-select-${field.name}">
                            ${optionsHtml}
                        </select>
                    </div>
                `;
            } else {
                searchHtml += `
                    <div class="layui-inline">
                        <input type="text" name="${field.name}" 
                               placeholder="${field.placeholder || ''}" 
                               class="layui-input" 
                               style="width: ${field.width || 200}px;">
                    </div>
                `;
            }
        });
        
        let buttonsHtml = '';
        buttonsHtml += `
            <button class="layui-btn layui-btn-sm" lay-submit lay-filter="search">
                <i class="layui-icon layui-icon-search"></i> 搜索
            </button>
            <button type="reset" class="layui-btn layui-btn-sm layui-btn-primary">重置</button>
        `;
        
        toolbar.forEach(btn => {
            // 权限检查
            if (btn.permission && !this.checkPermission(btn.permission)) {
                return;
            }
            
            buttonsHtml += `
                <button type="button" class="layui-btn layui-btn-sm ${btn.class || ''}" 
                        data-action="${btn.action}">
                    <i class="layui-icon ${btn.icon}"></i> ${btn.text}
                </button>
            `;
        });
        
        return `
            <div class="table-toolbar">
                <form class="layui-form" lay-filter="searchForm" style="margin: 0;">
                    <div class="layui-form-item" style="margin-bottom: 0;">
                        ${searchHtml}
                        <div class="layui-inline">
                            ${buttonsHtml}
                        </div>
                    </div>
                </form>
            </div>
        `;
    }
    
    /**
     * 导出数据
     */
    exportData() {
        const form = layui.form;
        const formData = form.val('searchForm');
        const params = new URLSearchParams(formData);
        
        let exportUrl = this.config.api.export;
        if (!exportUrl) {
             exportUrl = this.config.api.list + '/export';
        }
        
        const separator = exportUrl.includes('?') ? '&' : '?';
        const url = API_BASE + exportUrl + separator + params.toString();
        
        window.open(url, '_blank');
    }

    /**
     * 渲染表格占位符
     */
    renderTablePlaceholder() {
        return `<table class="layui-hide" id="crud-table" lay-filter="crud-table"></table>`;
    }
    
    /**
     * 加载下拉框选项数据（用于表格回显）
     */
    async loadSelectOptions() {
        const promises = [];
        
        // 兼容 Layui table cols 的格式：[[...]] 或 [...]
        // 通常是 [[{field:...}, {field:...}]]
        let cols = this.config.table.cols;
        if (!Array.isArray(cols[0])) {
            cols = [cols];
        }
        
        // 遍历所有列，查找 select 类型
        cols.forEach(row => {
            row.forEach(col => {
                if (col.type !== 'select') return;
                
                const fieldName = col.field;
                let options = col.options;
                let url = col.url;
                let valueField = col.valueField || 'id';
                let labelField = col.labelField || 'name';
                
                // 如果表格配置中没有，尝试从表单配置中查找
                if (!options && !url && this.config.form) {
                    const formField = this.config.form.find(f => f.name === fieldName);
                    if (formField) {
                        options = formField.options;
                        url = formField.url;
                        if (formField.valueField) valueField = formField.valueField;
                        if (formField.labelField) labelField = formField.labelField;
                    }
                }
                
                this.selectMap[fieldName] = {};
                
                if (options) {
                    // 静态选项
                    options.forEach(opt => {
                        this.selectMap[fieldName][opt.value] = {
                            label: opt.label || opt.title,
                            color: opt.color
                        };
                    });
                } else if (url) {
                    // 动态选项
                    const p = request(url, { method: 'GET' }).then(res => {
                        if (res.code === 0) {
                            const list = res.data.list || res.data;
                            if (Array.isArray(list)) {
                                list.forEach(item => {
                                    this.selectMap[fieldName][item[valueField]] = {
                                        label: item[labelField],
                                        color: item.color // 尝试从动态数据中获取颜色
                                    };
                                });
                            }
                        }
                    }).catch(err => console.error('加载下拉数据失败', err));
                    promises.push(p);
                }
            });
        });
        
        await Promise.all(promises);
    }

    /**
     * 渲染表格
     */
    async renderTable() {
        const table = layui.table;
        const tableConfig = this.config.table;

        // 预处理列配置，处理自动识别类型
        tableConfig.cols = tableConfig.cols.map(item => {
            // 浅拷贝，避免修改原配置
            let col = { ...item };

            // 自动识别类型
            if (col.type === 'auto') {
                const formField = this.config.form ? this.config.form.find(f => f.name === col.field) : null;
                if (formField) {
                    switch (formField.type) {
                        case 'select':
                        case 'radio':
                        case 'checkbox':
                            col.type = 'select';
                            if (!col.options && formField.options) col.options = formField.options;
                            if (!col.url && formField.url) col.url = formField.url;
                            if (!col.valueField && formField.valueField) col.valueField = formField.valueField;
                            if (!col.labelField && formField.labelField) col.labelField = formField.labelField;
                            break;
                        case 'switch':
                            col.type = 'switch';
                            if (!col.text && formField.text) col.text = formField.text;
                            if (col.checkedValue === undefined && formField.checkedValue !== undefined) col.checkedValue = formField.checkedValue;
                            if (col.uncheckedValue === undefined && formField.uncheckedValue !== undefined) col.uncheckedValue = formField.uncheckedValue;
                            break;
                        case 'image':
                            col.type = 'image';
                            break;
                        case 'file':
                            col.type = 'file';
                            break;
                        case 'date':
                            col.type = 'date';
                            break;
                        case 'datetime':
                        case 'timestamp':
                            col.type = 'datetime';
                            break;
                        case 'rate':
                            col.type = 'rate';
                            if (!col.length && formField.length) col.length = formField.length;
                            if (!col.theme && formField.theme) col.theme = formField.theme;
                            break;
                        case 'tags':
                            col.type = 'tags';
                            break;
                        case 'textarea':
                        case 'editor':
                            col.type = ''; // 文本域和富文本识别为默认文本
                            break;
                        case 'icon':
                            col.type = 'icon';
                            break;
                        case 'slider':
                        case 'progress':
                             col.type = 'progress';
                             if (!col.theme && formField.theme) col.theme = formField.theme;
                             break;
                        default:
                            col.type = '';
                    }
                } else {
                    col.type = '';
                }
            }
            // 开关类型
            if (col.type === 'switch') {
                // 如果没有配置 text，尝试从表单配置中继承
                if (!col.text && this.config.form) {
                    const formField = this.config.form.find(f => f.name === col.field);
                    if (formField && formField.text) {
                        col.text = formField.text;
                    }
                }
                
                return {
                    ...col,
                    templet: (d) => {
                        const val = d[col.field];
                        // 强制 1 为 checked
                        const checked = val == 1 ? 'checked' : '';
                        const text = col.text || 'ON|OFF';
                        
                        // 随机ID避免冲突
                        const id = 'switch_' + col.field + '_' + d.id;
                        
                        return `<input type="checkbox" name="${col.field}" value="${d.id}" lay-skin="switch" lay-text="${text}" lay-filter="table-switch" ${checked}>`;
                    }
                };
            }
            return col;
        });

        await this.loadSelectOptions();

        // 处理列配置，支持特殊类型
        const cols = tableConfig.cols.map(col => {
            // 文件类型
            if (col.type === 'file') {
                return {
                    ...col,
                    templet: (d) => {
                        const url = d[col.field];
                        if (!url) return '-';
                        return `<a href="${url}" target="_blank" class="layui-table-link" style="color: #1E9FFF;">查看文件</a>`;
                    }
                };
            }
            // 图片类型
            if (col.type === 'image') {
                return {
                    ...col,
                    templet: (d) => {
                        const url = d[col.field];
                        if (!url) return '-';
                        return `<div onclick="layui.layer.photos({photos: {data: [{src: '${url}'}]}, anim: 5})" style="cursor: pointer;">
                                    <img src="${url}" style="height: 30px; max-width: 100%;">
                                </div>`;
                    }
                };
            }
            // 状态开关类型
            if (col.type === 'switch') {
                return {
                    ...col,
                    templet: (d) => {
                        const checkedValue = col.checkedValue !== undefined ? col.checkedValue : 1;
                        const uncheckedValue = col.uncheckedValue !== undefined ? col.uncheckedValue : 0;
                        const checked = d[col.field] == checkedValue ? 'checked' : '';
                        return `<input type="checkbox" name="${col.field}" value="${d.id}" 
                                       lay-skin="switch" lay-text="${col.text || 'ON|OFF'}" 
                                       lay-filter="table-switch" 
                                       data-checked-value="${checkedValue}"
                                       data-unchecked-value="${uncheckedValue}"
                                       ${checked}>`;
                    }
                };
            }
            // 状态标签类型
            if (col.type === 'tags') {
                return {
                    ...col,
                    templet: (d) => {
                        const val = d[col.field];
                        if (!val) return '';
                        // 支持逗号分隔
                        const tags = String(val).split(',');
                        return tags.map(tag => `<span class="layui-badge layui-bg-blue" style="margin-right: 5px;">${tag}</span>`).join('');
                    }
                };
            }
            // 图标类型
            if (col.type === 'icon') {
                return {
                    ...col,
                    templet: (d) => {
                        const val = d[col.field];
                        if (!val) return '';
                        return `<i class="layui-icon ${val}" style="font-size: 20px;"></i>`;
                    }
                };
            }
            // 超链接类型
            if (col.type === 'link') {
                return {
                    ...col,
                    templet: (d) => {
                        const val = d[col.field];
                        let href = val;
                        
                        // 支持 URL 模板
                        if (col.url) {
                             href = col.url.replace(/\{(\w+)\}/g, (match, key) => d[key] !== undefined ? d[key] : match);
                        }
                        
                        // 文本优先顺序：配置的文本 -> 字段值 -> 默认文本
                        const text = col.text || val || '查看';
                        
                        if (!href) return '-';
                        return `<a href="${href}" target="_blank" class="layui-table-link" style="color: #1E9FFF;">${text}</a>`;
                    }
                };
            }
            // 进度条类型
            if (col.type === 'progress') {
                return {
                    ...col,
                    templet: (d) => {
                        const val = d[col.field] || 0;
                        let color = col.theme || '#1E9FFF';
                        
                        // 从表单配置继承颜色
                        if (this.config.form) {
                            const formField = this.config.form.find(f => f.name === col.field);
                            if (formField && formField.theme) {
                                color = formField.theme;
                            }
                        }

                        // 随机ID避免冲突
                        const id = 'progress_' + col.field + '_' + d.id;
                        // 延迟渲染进度条
                        setTimeout(() => {
                            layui.element.render('progress', id);
                        }, 50);
                        return `<div class="layui-progress layui-progress-big" lay-showpercent="true" lay-filter="${id}">
                                    <div class="layui-progress-bar" lay-percent="${val}%" style="background-color: ${color};"></div>
                                </div>`;
                    }
                };
            }
            // 评分类型
            if (col.type === 'rate') {
                return {
                    ...col,
                    templet: (d) => {
                        const val = d[col.field] || 0;
                        const length = col.length || 5;
                        let theme = col.theme || '#FFB800';

                        // 从表单配置继承颜色
                        if (this.config.form) {
                            const formField = this.config.form.find(f => f.name === col.field);
                            if (formField && formField.theme) {
                                theme = formField.theme;
                            }
                        }

                        const id = 'rate_' + col.field + '_' + d.id;
                        
                        // 延迟渲染评分
                        setTimeout(() => {
                            layui.rate.render({
                                elem: '#' + id,
                                value: val,
                                length: length,
                                theme: theme,
                                readonly: true
                            });
                        }, 50);
                        
                        return `<div id="${id}" style="margin-top: -8px;"></div>`;
                    }
                };
            }
            // 数据映射类型（原下拉选择）
            if (col.type === 'select') {
                return {
                    ...col,
                    templet: (d) => {
                        let val = d[col.field];
                        if (val === null || val === undefined) return '';
                        
                        const map = this.selectMap[col.field];
                        if (!map) return val;

                        const renderItem = (v) => {
                            const key = String(v).trim();
                            // 尝试直接获取或转为字符串获取
                            const item = map[key];
                            
                            if (!item) return key;
                            
                            // 兼容旧格式（如果是字符串）
                            if (typeof item === 'string') return item;
                            
                            const label = item.label || key;
                            
                            if (item.color) {
                                // 判断是否为 Layui 内置颜色类
                                const isLayuiColor = ['red', 'orange', 'green', 'cyan', 'blue', 'black', 'gray'].includes(item.color);
                                const classAttr = isLayuiColor ? `layui-badge layui-bg-${item.color}` : 'layui-badge';
                                const styleAttr = !isLayuiColor ? `background-color: ${item.color};` : '';
                                return `<span class="${classAttr}" style="${styleAttr}">${label}</span>`;
                            }
                            
                            return label;
                        };

                        // 转换为字符串处理，以支持逗号分隔的多选值
                        const valStr = String(val);
                        
                        // 如果包含逗号，尝试分割并映射每个值
                        if (valStr.includes(',')) {
                             return valStr.split(',').map(renderItem).join(' ');
                        }
                        
                        // 单个值直接映射
                        return renderItem(valStr);
                    }
                };
            }
            // 时间类型
            if (col.type === 'datetime') {
                return {
                    ...col,
                    templet: (d) => {
                        let val = d[col.field];
                        if (!val) return '-';
                        
                        // 如果是数字且长度为10位（秒级时间戳），转换为毫秒
                        if (/^\d{10}$/.test(val)) {
                            val = val * 1000;
                        } else if (/^\d{13}$/.test(val)) {
                             // 毫秒级时间戳，转为数字
                             val = parseInt(val);
                        }
                        
                        // 使用 layui.util 格式化
                        return layui.util.toDateString(val, 'yyyy-MM-dd HH:mm:ss');
                    }
                };
            }
            // 日期类型
            if (col.type === 'date') {
                return {
                    ...col,
                    templet: (d) => {
                        let val = d[col.field];
                        if (!val) return '-';
                        
                        // 如果是数字且长度为10位（秒级时间戳），转换为毫秒
                        if (/^\d{10}$/.test(val)) {
                            val = val * 1000;
                        } else if (/^\d{13}$/.test(val)) {
                             // 毫秒级时间戳，转为数字
                             val = parseInt(val);
                        }
                        
                        // 使用 layui.util 格式化
                        return layui.util.toDateString(val, 'yyyy-MM-dd');
                    }
                };
            }
            return col;
        });

        // 动态注入操作列（如果配置了 actions）
        if (this.config.actions && this.config.actions.length > 0) {
            // 移除旧的 toolbar 列（如果有）
            const cleanCols = cols.filter(c => !c.toolbar);
            
            // 获取操作列宽度配置，默认为 200；移动端缩小宽度且不 sticky
            const isMobile = window.innerWidth <= 768;
            const actionsWidth = isMobile
                ? Math.min(this.config.actions.length * 58, 160)
                : (this.config.table.actionsWidth || 200);
            
            cleanCols.push({
                fixed: isMobile ? '' : 'right',
                title: '操作',
                width: actionsWidth,
                templet: (d) => {
                    let html = '';
                    this.config.actions.forEach(btn => {
                        // 权限检查
                        if (btn.permission && !this.checkPermission(btn.permission)) {
                            return;
                        }
                        
                        const className = btn.class || 'layui-btn-primary';
                        const iconHtml = btn.icon ? `<i class="layui-icon ${btn.icon}"></i>` : '';
                        
                        html += `<a class="layui-btn layui-btn-xs ${className}" lay-event="${btn.action}">
                                    ${iconHtml} ${btn.text}
                                 </a>`;
                    });
                    return html;
                }
            });
            
            // 更新 cols
            // tableConfig.cols = [cleanCols]; // 这一行修改了配置对象，但没有影响到下面的 table.render
            
            // 重新赋值给 render 使用的 cols 变量
            // 注意：table.render 需要的是 [[...]] 格式
            var finalCols = [cleanCols];
        } else {
            // 保持原样
            // tableConfig.cols = [cols];
            var finalCols = [cols];
        }

        // ========== 表格列宽自适应处理 ==========
        // 扁平化列数组
        const flatCols = finalCols[0];

        // 主要字段候选列表（优先级从高到低）
        const mainFields = ['title', 'name', 'username', 'nickname', 'description', 'content', 'email', 'remark'];
        let adaptiveColFound = false;

        // 辅助：判断列是否适合作为自适应列（非固定、非操作、非ID、非多选）
        const isAdaptable = (col) =>
            !col.fixed && col.title !== '操作' && col.field !== 'id' &&
            col.type !== 'checkbox' && col.type !== 'switch';

        // 1. 优先找 mainFields 中【没有配置 width 的列】→ 让它自适应
        for (const field of mainFields) {
            const col = flatCols.find(c => c.field === field && !c.width && isAdaptable(c));
            if (col) {
                col.minWidth = col.minWidth || 200;
                adaptiveColFound = true;
                break;
            }
        }

        // 2. 找其他【没有配置 width 的合适列】→ 让它自适应
        if (!adaptiveColFound) {
            for (let i = flatCols.length - 1; i >= 0; i--) {
                const col = flatCols[i];
                if (isAdaptable(col) && !col.width) {
                    col.minWidth = col.minWidth || 150;
                    adaptiveColFound = true;
                    break;
                }
            }
        }

        // 如果用户给所有列都配了宽度，则尊重配置，
        // 不强制覆盖任何 width，由 Layui 自然处理布局。
        // ======================================

        this.tableIns = table.render({
            elem: '#crud-table',
            url: API_BASE + tableConfig.url,
            headers: {
                'Authorization': getToken()
            },
            page: tableConfig.page !== false,
            limit: tableConfig.limit || 10,
            limits: [10, 20, 50, 100],
            cols: finalCols
        });
        
        // 保存到全局
        window.currentTableIns = this.tableIns;
    }
    
    /**
     * 绑定事件
     */
    bindEvents() {
        const form = layui.form;
        const table = layui.table;
        const layer = layui.layer;
        
        // 搜索事件
        form.on('submit(search)', (data) => {
            this.tableIns.reload({
                where: data.field,
                page: { curr: 1 }
            });
            return false;
        });
        
        // 工具栏按钮事件
        this.container.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                
                // 优先查找配置中的 action
                let actionConfig = null;
                if (this.config.toolbar) {
                    actionConfig = this.config.toolbar.find(b => b.action === action);
                }

                if (action === 'add') {
                    this.showFormDialog();
                } else if (action === 'export') {
                    this.exportData();
                } else if (actionConfig) {
                    // 处理自定义工具栏按钮
                    if (actionConfig.type === 'page' && actionConfig.url) {
                        let url = actionConfig.url;
                        if (!url.startsWith('http') && !url.startsWith('/')) {
                            url = API_BASE + url;
                        }
                        // 移除 Token 自动追加，改为依赖 Cookie
                        window.open(url, '_blank');
                    } else if (actionConfig.type === 'iframe' && actionConfig.url) {
                        let url = actionConfig.url;
                        if (!url.startsWith('http') && !url.startsWith('/')) {
                            url = API_BASE + url;
                        }
                        // 移除 Token 自动追加，改为依赖 Cookie
                        
                        layer.open({
                            type: 2,
                            title: actionConfig.text || '操作',
                            shadeClose: true,
                            shade: 0,
                            maxmin: true,
                            area: [actionConfig.width || '80%', actionConfig.height || '90%'],
                            content: url
                        });
                    }
                }
            });
        });
        
        // 表格行工具栏事件
        table.on('tool(crud-table)', (obj) => {
            const data = obj.data;
            
            if (obj.event === 'edit') {
                this.showFormDialog(data);
            } else if (obj.event === 'delete') {
                this.deleteRow(data, obj);
            } else {
                // 处理自定义动作
                if (this.config.actions) {
                    const actionConfig = this.config.actions.find(a => a.action === obj.event);
                    if (actionConfig) {
                        if (actionConfig.type === 'page' && actionConfig.url) {
                            // 打开自定义页面，替换 URL 中的变量
                            let url = actionConfig.url;
                            for (const key in data) {
                                url = url.replace(`{${key}}`, data[key]);
                            }
                            // 检查是否是绝对路径
                            if (!url.startsWith('http') && !url.startsWith('/')) {
                                url = API_BASE + url; // 或者其他基础路径
                            }

                            // 移除 Token 自动追加，改为依赖 Cookie
                            
                            // 在新窗口打开或当前窗口打开
                            window.open(url, '_blank');
                        } else if (actionConfig.type === 'iframe' && actionConfig.url) {
                            // 弹窗打开 iframe
                            let url = actionConfig.url;
                            for (const key in data) {
                                url = url.replace(`{${key}}`, data[key]);
                            }
                            if (!url.startsWith('http') && !url.startsWith('/')) {
                                url = API_BASE + url;
                            }

                            // 移除 Token 自动追加，改为依赖 Cookie
                            
                            layer.open({
                                type: 2,
                                title: actionConfig.text || '预览',
                                shadeClose: true,
                                shade: 0, // 去掉遮罩
                                maxmin: true, // 允许最大化
                                area: [actionConfig.width || '80%', actionConfig.height || '90%'],
                                content: url
                            });
                        } else if (actionConfig.callback) {
                            // 如果支持回调函数（需要 eval 或预定义函数，这里暂不实现复杂回调）
                            console.log('Custom action triggered:', actionConfig);
                        }
                    }
                }
            }
        });

        // 监听表格开关切换
        form.on('switch(table-switch)', (obj) => {
            const id = obj.value;
            const field = obj.elem.name;
            const checkedValue = obj.elem.dataset.checkedValue;
            const uncheckedValue = obj.elem.dataset.uncheckedValue;
            const newValue = obj.elem.checked ? checkedValue : uncheckedValue;
            
            // 构建更新数据
            const data = {};
            data[field] = newValue;
            
            // 调用更新接口
            let url = this.config.api.edit.replace('{id}', id);
            
            request(url, {
                method: 'POST',
                data: data
            }).then(res => {
                if (res.code === 0) {
                    layer.msg('状态更新成功', { icon: 1, time: 1000 });
                } else {
                    layer.msg(res.msg || '更新失败', { icon: 2 });
                    // 失败时回滚状态
                    obj.elem.checked = !obj.elem.checked;
                    form.render('checkbox');
                }
            });
        });
    }
    
    /**
     * 显示表单弹窗
     */
    showFormDialog(rowData = null) {
        const layer = layui.layer;
        const form = layui.form;
        
        const isEdit = !!rowData;
        const title = isEdit ? '编辑' : '新增';
        
        // 根据字段数量动态调整弹窗大小
        const formFieldCount = this.config.form?.length || 0;
        const hasRichText = this.config.form?.some(f => f.type === 'editor');
        const hasUpload = this.config.form?.some(f => f.type === 'upload' || f.type === 'image');
        
        // 动态宽度：有富文本或上传时使用更大宽度
        const width = hasRichText ? '90%' : (hasUpload ? '900px' : '800px');
        const height = hasRichText ? '90%' : '80%';
        
        layer.open({
            type: 1,
            title: title + this.config.page.title,
            area: [width, height],
            content: `
                <form class="layui-form" lay-filter="crudForm" style="padding: 20px;">
                    ${this.renderFormFields(rowData)}
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn" lay-submit lay-filter="submitForm">提交</button>
                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                        </div>
                    </div>
                </form>
            `,
            success: (layero, index) => {
                // 初始化表单
                this.initFormComponents(rowData, isEdit);
                form.render();
                
                // 提交表单
                form.on('submit(submitForm)', (formData) => {
                    const submitData = formData.field;
                    
                    // 手动添加隐藏字段的值（Layui 可能不会自动收集）
                    this.config.form.forEach((field, idx) => {
                        const fieldId = `field_${field.name}_${idx}`;
                        
                        // 权限字段
                        if (field.type === 'permissions') {
                            const hiddenInput = document.getElementById(fieldId + '_value');
                            if (hiddenInput) {
                                submitData[field.name] = hiddenInput.value;
                            }
                        }
                        
                        // Switch 字段
                        if (field.type === 'switch') {
                            const hiddenInput = document.getElementById(fieldId + '_value');
                            if (hiddenInput) {
                                submitData[field.name] = hiddenInput.value;
                            }
                        }

                        // 时间戳字段转换 (String -> Timestamp)
                        if (field.type === 'timestamp' && submitData[field.name]) {
                            const dateStr = submitData[field.name];
                            // 尝试转换为时间戳（秒）
                            const dateObj = new Date(dateStr.replace(/-/g, '/')); // 兼容性更好的格式 yyyy/MM/dd HH:mm:ss
                            if (!isNaN(dateObj.getTime())) {
                                submitData[field.name] = Math.floor(dateObj.getTime() / 1000);
                            }
                        }
                    });
                    
                    this.submitForm(submitData, isEdit, rowData?.id, index);
                    return false;
                });
            },
            end: () => {
                // 销毁所有富文本编辑器实例
                for (let key in this.editors) {
                    if (this.editors[key] && this.editors[key].destroy) {
                        this.editors[key].destroy();
                    }
                }
                this.editors = {};
            }
        });
    }
    
    /**
     * 解析静态选项配置
     * @param {string|Array} optionsStr 
     */
    parseOptions(optionsStr) {
        if (!optionsStr) return [];
        if (Array.isArray(optionsStr)) return optionsStr;
        return String(optionsStr).split('\n')
            .filter(line => line.trim())
            .map(line => {
                // 支持 value:label 格式
                const parts = line.split(/[:：]/);
                const val = parts[0].trim();
                const label = parts[1] ? parts[1].trim() : val;
                return { value: val, label: label };
            });
    }

    /**
     * 渲染表单字段
     */
    renderFormFields(rowData) {
        let html = '';
        
        this.config.form.forEach((field, index) => {
            const value = rowData ? (rowData[field.name] || '') : (field.default || '');
            const isEdit = !!rowData;  // 是否为编辑模式
            const isAdd = !rowData;    // 是否为新增模式
            
            // ========== 字段隐藏规则 ==========
            // 新增时隐藏
            if (isAdd && field.hidden_on_add === true) {
                return;  // 跳过此字段
            }
            
            // 编辑时隐藏
            if (isEdit && field.hidden_on_edit === true) {
                return;  // 跳过此字段
            }
            
            // 超级管理员特殊隐藏（ID=1 编辑时隐藏）
            if (isEdit && rowData.id == 1 && field.hidden_on_super_admin === true) {
                return;  // 跳过此字段
            }
            
            // ========== 字段必填规则 ==========
            let required = false;
            
            // 新格式（优先，更明确）
            if (field.required_on_add === true && isAdd) {
                required = true;
            }
            if (field.required_on_edit === true && isEdit) {
                required = true;
            }
            if (field.required_on_both === true) {
                required = true;
            }
            
            // 旧格式（向后兼容）
            if (field.required === true) {
                required = true;
            }
            if (field.required === 'add' && isAdd) {
                required = true;
            }
            if (field.required === 'edit' && isEdit) {
                required = true;
            }
            
            // ========== 字段禁用规则 ==========
            let disabled = false;
            
            // 新增时禁用
            if (isAdd && field.disabled_on_add === true) {
                disabled = true;
            }
            
            // 编辑时禁用
            if (isEdit && field.disabled_on_edit === true) {
                disabled = true;
            }
            
            const fieldId = `field_${field.name}_${index}`;
            
            // 构建验证规则
            let verifyRules = [];
            if (required) verifyRules.push('required');
            if (field.verify) {
                // 支持 verify 为字符串或数组
                const rules = Array.isArray(field.verify) ? field.verify : field.verify.split('|');
                rules.forEach(r => {
                    if (r && r !== 'required') verifyRules.push(r);
                });
            }
            const verifyAttr = verifyRules.length > 0 ? `lay-verify="${verifyRules.join('|')}"` : '';
            const requiredAttr = required ? 'required' : '';
            
            // 必填项红星标记
            const requiredStar = required ? '<span style="color: red; margin-right: 4px;">*</span>' : '';

            html += '<div class="layui-form-item">';
            html += `<label class="layui-form-label" style="width: 110px;">${requiredStar}${field.label}</label>`;
            html += '<div class="layui-input-block" style="margin-left: 140px;">';
            
            switch (field.type) {
                case 'input':
                case 'password':
                    html += `<input type="${field.inputType || field.type}" 
                                    name="${field.name}" 
                                    id="${fieldId}"
                                    value="${value}" 
                                    placeholder="${field.placeholder || ''}"
                                    ${requiredAttr} ${verifyAttr}
                                    ${disabled ? 'disabled' : ''}
                                    class="layui-input">`;
                    break;
                    
                case 'textarea':
                    html += `<textarea name="${field.name}" 
                                       id="${fieldId}"
                                       ${requiredAttr} ${verifyAttr}
                                       ${disabled ? 'disabled' : ''}
                                       class="layui-textarea" 
                                       rows="${field.rows || 5}">${value}</textarea>`;
                    break;
                    
                case 'editor':
                    // 富文本编辑器
                    html += `<textarea name="${field.name}" 
                                       id="${fieldId}"
                                       style="display:none;">${value}</textarea>
                             <div id="${fieldId}_editor" style="height: ${field.height || '400px'}"></div>`;
                    break;
                    
                case 'radio':
                    const radioOptions = this.parseOptions(field.options);
                    if (radioOptions.length > 0) {
                        radioOptions.forEach(opt => {
                            const checked = rowData ? (value == opt.value) : (opt.checked || value === opt.value);
                            html += `<input type="radio" 
                                            name="${field.name}" 
                                            value="${opt.value}" 
                                            title="${opt.label || opt.title}"
                                            ${checked ? 'checked' : ''}>`;
                        });
                    } else if (field.url) {
                        html += `<div class="dynamic-source" 
                                      data-type="radio" 
                                      data-name="${field.name}" 
                                      data-source-url="${API_BASE + field.url}" 
                                      data-value="${value}"
                                      data-value-field="${field.valueField || 'id'}" 
                                      data-label-field="${field.labelField || 'name'}">
                                      <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 加载中...
                                 </div>`;
                    } else {
                        html += '<div class="layui-form-mid layui-word-aux">未配置选项数据</div>';
                    }
                    break;

                case 'checkbox':
                    const checkboxOptions = this.parseOptions(field.options);
                    const currentValues = value ? String(value).split(',') : [];
                    
                    html += `<div id="${fieldId}_container">`;
                    
                    if (checkboxOptions.length > 0) {
                        checkboxOptions.forEach(opt => {
                            const checked = currentValues.includes(String(opt.value));
                            html += `<input type="checkbox" 
                                            name="${field.name}[]" 
                                            value="${opt.value}" 
                                            title="${opt.label || opt.title}" 
                                            lay-skin="primary"
                                            lay-filter="checkbox-${field.name}"
                                            ${checked ? 'checked' : ''}>`;
                        });
                    } else if (field.url) {
                        html += `<div class="dynamic-source" 
                                      data-type="checkbox" 
                                      data-name="${field.name}" 
                                      data-source-url="${API_BASE + field.url}" 
                                      data-value="${value}"
                                      data-value-field="${field.valueField || 'id'}" 
                                      data-label-field="${field.labelField || 'name'}">
                                      <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 加载中...
                                 </div>`;
                    } else {
                        html += '<div class="layui-form-mid layui-word-aux">未配置选项数据</div>';
                    }
                    
                    html += `</div>
                             <input type="hidden" name="${field.name}" id="${fieldId}_value" value="${value}">`;
                    break;
                    
                case 'select':
                    // 解析静态选项
                    const staticOptions = this.parseOptions(field.options);
                    
                    // 动态数据源支持 (仅在无静态选项时启用)
                    let dynamicAttrs = '';
                    if (field.url && staticOptions.length === 0) {
                        dynamicAttrs = ` data-source-url="${API_BASE + field.url}" 
                                         data-value-field="${field.valueField || 'id'}" 
                                         data-label-field="${field.labelField || 'name'}" `;
                    }
                    
                    html += `<select name="${field.name}" id="${fieldId}" ${requiredAttr} ${verifyAttr} ${disabled ? 'disabled' : ''} ${dynamicAttrs}>`;
                    
                    if (!required) {
                        html += '<option value="">请选择</option>';
                    }
                    
                    if (staticOptions.length > 0) {
                        staticOptions.forEach(opt => {
                            const selected = value == opt.value ? 'selected' : '';
                            html += `<option value="${opt.value}" ${selected}>${opt.label || opt.title}</option>`;
                        });
                    } else if (field.url) {
                        // 如果是动态数据，初始显示 Loading 或当前值
                        html += `<option value="${value}" selected>加载中...</option>`;
                    }
                    
                    html += '</select>';
                    break;
                    
                case 'switch':
                    // 开关
                    // 确定当前值：如果有值则使用，否则检查是否有 switch_default (1或0)
                    let switchVal = value;
                    if (switchVal === '' || switchVal === undefined || switchVal === null) {
                        if (field.switch_default !== undefined && field.switch_default !== '') {
                            switchVal = field.switch_default;
                        } else {
                            // 默认关闭 (0)
                            switchVal = 0;
                        }
                    }
                    
                    // 强制转换为数字比较
                    const switchChecked = parseInt(switchVal) === 1;
                    
                    html += `<input type="checkbox" 
                                    name="${field.name}_switch" 
                                    id="${fieldId}"
                                    lay-skin="switch" 
                                    lay-text="${field.text || 'ON|OFF'}"
                                    lay-filter="switch-${field.name}"
                                    data-field-name="${field.name}"
                                    data-checked-value="1"
                                    data-unchecked-value="0"
                                    ${switchChecked ? 'checked' : ''}>
                             <input type="hidden" name="${field.name}" id="${fieldId}_value" value="${switchChecked ? 1 : 0}">`;
                    break;
                    
                case 'date':
                case 'datetime':
                case 'time':
                case 'timestamp':
                    // 日期/时间选择器
                    html += `<input type="text" 
                                    name="${field.name}" 
                                    id="${fieldId}"
                                    value="${value}" 
                                    placeholder="${field.placeholder || '请选择'}"
                                    ${requiredAttr} ${verifyAttr}
                                    class="layui-input"
                                    data-date-type="${field.type}">`;
                    break;
                    
                case 'upload':
                case 'image':
                    // 文件上传
                    const isImage = field.type === 'image';
                    const fileDisplay = isImage ? '' : (value ? `<a href="${value}" target="_blank" id="${fieldId}_link" style="margin-right: 10px; color: #1E9FFF;">查看文件</a>` : `<a href="javascript:;" target="_blank" id="${fieldId}_link" style="display:none; margin-right: 10px; color: #1E9FFF;">查看文件</a>`);
                    
                    html += `<button type="button" class="layui-btn" id="${fieldId}_btn">
                                <i class="layui-icon layui-icon-upload"></i> 选择文件
                             </button>
                             <input type="hidden" name="${field.name}" id="${fieldId}" value="${value}">
                             <div class="layui-upload-list" style="margin-top: 10px;">
                                ${isImage ? `<img src="${value || ''}" style="max-width: 200px; max-height: 200px; display: ${value ? 'block' : 'none'}; margin-bottom: 10px;" id="${fieldId}_preview">` : ''}
                                <div id="${fieldId}_actions" style="${value ? '' : 'display: none;'}">
                                    ${fileDisplay}
                                    <span id="${fieldId}_text" style="margin-right: 10px; display: ${isImage ? 'inline' : 'none'}">已上传</span>
                                    <button type="button" class="layui-btn layui-btn-xs layui-btn-danger" id="${fieldId}_delete">
                                        <i class="layui-icon layui-icon-delete"></i> 删除
                                    </button>
                                </div>
                             </div>`;
                    break;
                    
                case 'color':
                    // 颜色选择器
                    html += `<div style="display: flex; align-items: center;">
                                <div id="${fieldId}_picker"></div>
                                <input type="hidden" name="${field.name}" id="${fieldId}" value="${value}">
                                <div style="margin-left: 10px; color: #666;">${value || ''}</div>
                             </div>`;
                    break;
                    
                case 'rate':
                    // 评分
                    html += `<div id="${fieldId}_rate"></div>
                             <input type="hidden" name="${field.name}" id="${fieldId}" value="${value}">`;
                    break;

                case 'slider':
                    // 滑块
                    html += `<div id="${fieldId}_slider" style="margin-top: 18px; width: 90%;"></div>
                             <input type="hidden" name="${field.name}" id="${fieldId}" value="${value}">`;
                    break;

                case 'icon':
                    // 图标选择
                    html += `<div class="layui-input-inline" style="width: auto;">
                                <input type="text" name="${field.name}" id="${fieldId}" value="${value}" 
                                       placeholder="${field.placeholder || '请选择图标'}" class="layui-input" 
                                       style="width: 200px;">
                             </div>
                             <div class="layui-input-inline" style="width: auto;">
                                <button type="button" class="layui-btn layui-btn-primary" id="${fieldId}_btn">
                                    <i class="layui-icon ${value || 'layui-icon-star'}"></i>
                                </button>
                             </div>`;
                    break;

                case 'tags':
                    // 标签输入
                    html += `<div class="layui-input" style="height: auto; min-height: 38px; padding: 4px 10px; display: flex; flex-wrap: wrap; align-items: center;">
                                <div id="${fieldId}_tags" style="display: contents;"></div>
                                <input type="text" id="${fieldId}_input" placeholder="${field.placeholder || '输入后回车'}" 
                                       style="border: none; outline: none; height: 30px; line-height: 30px; min-width: 100px; flex: 1;">
                                <input type="hidden" name="${field.name}" id="${fieldId}" value="${value}">
                             </div>`;
                    break;
                    
                case 'number':
                    // 数字输入
                    html += `<input type="number" 
                                    name="${field.name}" 
                                    id="${fieldId}"
                                    value="${value}" 
                                    min="${field.min || 0}"
                                    max="${field.max || ''}"
                                    step="${field.step || 1}"
                                    placeholder="${field.placeholder || ''}"
                                    ${requiredAttr} ${verifyAttr}
                                    class="layui-input">`;
                    break;
                    
                case 'slider':
                    // 滑块
                    html += `<div id="${fieldId}" style="margin-top: 10px;"></div>
                             <input type="hidden" name="${field.name}" id="${fieldId}_value" value="${value}">`;
                    break;
                
                case 'permissions':
                    // 权限配置（多选）
                    html += `<div id="${fieldId}" style="margin-top: 10px;">
                                <div style="color: #999; font-size: 12px; margin-bottom: 10px;">正在加载权限选项...</div>
                             </div>
                             <input type="hidden" name="${field.name}" id="${fieldId}_value" value='${value || ''}'>`;
                    break;
            }
            
            // 提示信息
            if (field.tip) {
                html += `<div class="layui-form-mid layui-word-aux">${field.tip}</div>`;
            }
            
            html += '</div></div>';
        });
        
        return html;
    }
    
    /**
     * 初始化表单组件（日期、上传、富文本等）
     */
    initFormComponents(rowData, isEdit) {
        const laydate = layui.laydate;
        const upload = layui.upload;
        const colorpicker = layui.colorpicker;
        const slider = layui.slider;
        const rate = layui.rate;
        const layer = layui.layer;
        
        this.config.form.forEach((field, index) => {
            const fieldId = `field_${field.name}_${index}`;
            const value = rowData ? (rowData[field.name] || '') : (field.default || '');
            
            // 颜色选择器
            if (field.type === 'color') {
                colorpicker.render({
                    elem: '#' + fieldId + '_picker',
                    color: value || field.theme || '#1E9FFF',
                    done: function(color){
                        $('#' + fieldId).val(color);
                        $('#' + fieldId + '_picker').next().next().text(color);
                    }
                });
            }

            // 评分
            if (field.type === 'rate') {
                rate.render({
                    elem: '#' + fieldId + '_rate',
                    value: value || 0,
                    length: field.length || 5,
                    theme: field.theme || '#FFB800',
                    choose: function(value){
                        $('#' + fieldId).val(value);
                    }
                });
            }

            // 标签输入
            if (field.type === 'tags') {
                const hidden = document.getElementById(fieldId);
                const container = document.getElementById(fieldId + '_tags');
                const input = document.getElementById(fieldId + '_input');
                
                if (hidden && container && input) {
                    const renderTags = () => {
                        const val = hidden.value;
                        const tags = val ? val.split(',') : [];
                        let html = '';
                        tags.forEach((tag, idx) => {
                            if(!tag.trim()) return;
                            html += `<span class="layui-badge layui-bg-blue tag-item" data-idx="${idx}" style="margin-right: 5px; cursor: pointer; padding: 4px 8px; margin-bottom: 4px; display: inline-flex; align-items: center;">${tag} <i class="layui-icon layui-icon-close" style="font-size: 12px; margin-left: 4px;"></i></span>`;
                        });
                        container.innerHTML = html;
                    };
                    
                    renderTags();
                    
                    // 删除标签 (事件委托)
                    container.addEventListener('click', (e) => {
                        const item = e.target.closest('.tag-item');
                        if (item) {
                            const idx = parseInt(item.dataset.idx);
                            const val = hidden.value;
                            let tags = val ? val.split(',') : [];
                            tags.splice(idx, 1);
                            hidden.value = tags.join(',');
                            renderTags();
                        }
                    });
                    
                    // 输入标签
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ',') {
                            e.preventDefault();
                            const val = input.value.trim().replace(/,/g, '');
                            if (val) {
                                const currentVal = hidden.value;
                                let tags = currentVal ? currentVal.split(',') : [];
                                if (!tags.includes(val)) {
                                    tags.push(val);
                                    hidden.value = tags.join(',');
                                    renderTags();
                                }
                                input.value = '';
                            }
                        } else if (e.key === 'Backspace' && !input.value) {
                             const currentVal = hidden.value;
                             let tags = currentVal ? currentVal.split(',') : [];
                             if (tags.length > 0) {
                                 tags.pop();
                                 hidden.value = tags.join(',');
                                 renderTags();
                             }
                        }
                    });
                }
            }

            // 滑块
            if (field.type === 'slider') {
                slider.render({
                    elem: '#' + fieldId + '_slider',
                    value: value || 0,
                    min: field.min || 0,
                    max: field.max || 100,
                    step: field.step || 1,
                    theme: field.theme || '#009688',
                    input: true, // 开启输入框
                    change: function(value){
                        $('#' + fieldId).val(value);
                    }
                });
            }

            // 图标选择
            if (field.type === 'icon') {
                $('#' + fieldId + '_btn').on('click', function() {
                    const icons = [
                        'layui-icon-heart-fill', 'layui-icon-heart', 'layui-icon-light', 'layui-icon-time', 'layui-icon-bluetooth',
                        'layui-icon-at', 'layui-icon-mute', 'layui-icon-mike', 'layui-icon-key', 'layui-icon-gift',
                        'layui-icon-email', 'layui-icon-rss', 'layui-icon-wifi', 'layui-icon-logout', 'layui-icon-android',
                        'layui-icon-ios', 'layui-icon-windows', 'layui-icon-transfer', 'layui-icon-service', 'layui-icon-subtraction',
                        'layui-icon-addition', 'layui-icon-slider', 'layui-icon-print', 'layui-icon-export', 'layui-icon-cols',
                        'layui-icon-screen-restore', 'layui-icon-screen-full', 'layui-icon-rate-half', 'layui-icon-rate', 'layui-icon-rate-solid',
                        'layui-icon-cellphone', 'layui-icon-vercode', 'layui-icon-login-wechat', 'layui-icon-login-qq', 'layui-icon-upload-drag',
                        'layui-icon-camera', 'layui-icon-user', 'layui-icon-file', 'layui-icon-home', 'layui-icon-delete',
                        'layui-icon-add-1', 'layui-icon-edit', 'layui-icon-search', 'layui-icon-password', 'layui-icon-username',
                        'layui-icon-refresh', 'layui-icon-console', 'layui-icon-theme', 'layui-icon-website', 'layui-icon-app',
                        'layui-icon-cart', 'layui-icon-date', 'layui-icon-fonts-strong', 'layui-icon-fonts-i', 'layui-icon-fonts-u',
                        'layui-icon-fonts-center', 'layui-icon-fonts-right', 'layui-icon-fonts-left', 'layui-icon-fonts-del', 'layui-icon-fonts-code',
                        'layui-icon-fonts-html', 'layui-icon-font-12', 'layui-icon-font-24', 'layui-icon-font-36', 'layui-icon-font-48',
                        'layui-icon-star', 'layui-icon-star-fill', 'layui-icon-close', 'layui-icon-close-fill', 'layui-icon-ok',
                        'layui-icon-ok-circle', 'layui-icon-error', 'layui-icon-play', 'layui-icon-pause', 'layui-icon-music',
                        'layui-icon-video', 'layui-icon-voice', 'layui-icon-speaker', 'layui-icon-rmb', 'layui-icon-dollar',
                        'layui-icon-diamond', 'layui-icon-location', 'layui-icon-place', 'layui-icon-check', 'layui-icon-return',
                        'layui-icon-loading', 'layui-icon-top', 'layui-icon-down', 'layui-icon-left', 'layui-icon-right',
                        'layui-icon-on', 'layui-icon-off', 'layui-icon-upload', 'layui-icon-download-circle', 'layui-icon-upload-circle',
                        'layui-icon-read', 'layui-icon-code-circle', 'layui-icon-tab', 'layui-icon-note', 'layui-icon-flag',
                        'layui-icon-component', 'layui-icon-file-b', 'layui-icon-user', 'layui-icon-find-fill', 'layui-icon-loading-1',
                        'layui-icon-home', 'layui-icon-set', 'layui-icon-engine', 'layui-icon-group', 'layui-icon-friends',
                        'layui-icon-reply-fill', 'layui-icon-menu-fill', 'layui-icon-log', 'layui-icon-picture', 'layui-icon-link',
                        'layui-icon-face-smile', 'layui-icon-face-cry', 'layui-icon-face-surprised', 'layui-icon-biaoqing', 'layui-icon-baby',
                        'layui-icon-triangle-r', 'layui-icon-triangle-d', 'layui-icon-set-sm', 'layui-icon-add-circle', 'layui-icon-layim-uploadfile',
                        'layui-icon-404', 'layui-icon-about', 'layui-icon-up', 'layui-icon-down', 'layui-icon-left', 'layui-icon-right',
                        'layui-icon-circle-dot', 'layui-icon-search', 'layui-icon-set-fill', 'layui-icon-group', 'layui-icon-friends'
                    ];

                    const renderIcons = (filter = '') => {
                        return icons.filter(icon => icon.includes(filter)).map(icon => 
                            `<div class="icon-item" title="${icon}" data-icon="${icon}" style="padding: 10px; cursor: pointer; border: 1px solid #eee; margin: 5px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; float: left;">
                                <i class="layui-icon ${icon}" style="font-size: 24px;"></i>
                             </div>`
                        ).join('');
                    };

                    layer.open({
                        type: 1,
                        title: '选择图标',
                        area: ['600px', '400px'],
                        content: `
                            <div style="padding: 10px;">
                                <div class="layui-form-item" style="margin-bottom: 10px;">
                                    <input type="text" id="icon-search-${fieldId}" placeholder="搜索图标..." class="layui-input">
                                </div>
                                <div id="icon-list-${fieldId}" style="height: 300px; overflow-y: auto; padding: 5px;">
                                    ${renderIcons()}
                                    <div style="clear: both;"></div>
                                </div>
                            </div>
                        `,
                        success: function(layero, index) {
                            // 绑定点击事件，只关闭当前层
                            $(`#icon-list-${fieldId}`).on('click', '.icon-item', function() {
                                const icon = $(this).data('icon');
                                $('#' + fieldId).val(icon);
                                $('#' + fieldId + '_btn i').attr('class', 'layui-icon ' + icon);
                                layer.close(index);
                            });

                            $(`#icon-search-${fieldId}`).on('input', function() {
                                const val = $(this).val();
                                $(`#icon-list-${fieldId}`).html(renderIcons(val) + '<div style="clear: both;"></div>');
                            });
                        }
                    });
                });
                
                // 监听输入框变化，实时更新预览
                $('#' + fieldId).on('input', function() {
                    const val = $(this).val();
                    if (val) {
                        $('#' + fieldId + '_btn i').attr('class', 'layui-icon ' + val);
                    }
                });
            }

            // 日期选择器
            if (['date', 'datetime', 'time', 'timestamp'].includes(field.type)) {
                const typeMap = {
                    'date': 'date',
                    'datetime': 'datetime',
                    'time': 'time',
                    'timestamp': 'datetime' // 时间戳使用日期时间选择器
                };
                
                // 时间戳特殊处理：如果值是数字，转换为格式化字符串
                if (field.type === 'timestamp' && /^\d{10}$/.test(value)) {
                    document.getElementById(fieldId).value = layui.util.toDateString(value * 1000, 'yyyy-MM-dd HH:mm:ss');
                }

                laydate.render({
                    elem: '#' + fieldId,
                    type: typeMap[field.type],
                    format: field.format || (['datetime', 'timestamp'].includes(field.type) ? 'yyyy-MM-dd HH:mm:ss' : (field.type === 'time' ? 'HH:mm:ss' : 'yyyy-MM-dd'))
                });
            }
            
            // 文件上传
            if (field.type === 'upload' || field.type === 'image') {
                let uploadOptions = {
                    elem: '#' + fieldId + '_btn',
                    url: API_BASE + (field.uploadUrl || '/api/admin/upload'),
                    accept: field.type === 'image' ? 'images' : 'file',
                    headers: {
                        'Authorization': getToken()
                    },
                    done: (res) => {
                        if (res.code === 0) {
                            document.getElementById(fieldId).value = res.data.url;
                            
                            if (field.type !== 'image') {
                                const link = document.getElementById(fieldId + '_link');
                                if (link) {
                                    link.href = res.data.url;
                                    link.style.display = 'inline';
                                    link.textContent = '查看文件';
                                }
                            } else {
                                document.getElementById(fieldId + '_text').textContent = '上传成功';
                                document.getElementById(fieldId + '_text').style.display = 'inline';
                            }
                            
                            // 显示操作区域
                            const actions = document.getElementById(fieldId + '_actions');
                            if (actions) actions.style.display = 'block';
                            
                            // 图片预览
                            if (field.type === 'image') {
                                const preview = document.getElementById(fieldId + '_preview');
                                if (preview) {
                                    preview.src = res.data.url;
                                    preview.style.display = 'block';
                                }
                            }
                        } else {
                            layer.msg('上传失败：' + res.msg, { icon: 2 });
                        }
                    },
                    error: () => {
                        layer.msg('上传失败', { icon: 2 });
                    }
                };
                
                if (field.exts) uploadOptions.exts = field.exts;
                if (field.size) uploadOptions.size = field.size;
                
                upload.render(uploadOptions);
                
                // 绑定删除事件
                const deleteBtn = document.getElementById(fieldId + '_delete');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', () => {
                        // 清空值
                        document.getElementById(fieldId).value = '';
                        document.getElementById(fieldId + '_text').textContent = '';
                        
                        // 隐藏文件链接
                        if (field.type !== 'image') {
                            const link = document.getElementById(fieldId + '_link');
                            if (link) {
                                link.href = 'javascript:;';
                                link.style.display = 'none';
                            }
                        }
                        
                        // 隐藏图片
                        if (field.type === 'image') {
                            const preview = document.getElementById(fieldId + '_preview');
                            if (preview) {
                                preview.src = '';
                                preview.style.display = 'none';
                            }
                        }
                        
                        // 隐藏操作区域
                        const actions = document.getElementById(fieldId + '_actions');
                        if (actions) actions.style.display = 'none';
                    });
                }
            }
            
            // 富文本编辑器（wangEditor）
            if (field.type === 'editor') {
                // 等待 DOM 渲染完成后初始化
                setTimeout(() => {
                    const editorContainer = document.getElementById(fieldId + '_editor');
                    
                    // 检查 wangEditor 是否加载（支持多种命名空间）
                    const WE = window.wangEditor || window.wangeditor;
                    
                    if (!editorContainer || !WE) {
                        console.error('wangEditor 未加载或容器不存在', {
                            container: !!editorContainer,
                            wangEditor: !!window.wangEditor,
                            wangeditor: !!window.wangeditor,
                            windowKeys: Object.keys(window).filter(k => k.toLowerCase().includes('wang'))
                        });
                        
                        // 降级为 textarea
                        const textarea = document.createElement('textarea');
                        textarea.name = field.name;
                        textarea.id = fieldId;
                        textarea.value = value || '';
                        textarea.className = 'layui-textarea';
                        textarea.rows = 10;
                        if (editorContainer && editorContainer.parentNode) {
                            editorContainer.parentNode.replaceChild(textarea, editorContainer);
                        }
                        return;
                    }
                    
                    try {
                        const { createEditor, createToolbar } = WE;
                        
                        // 编辑器配置
                        const editorConfig = {
                            placeholder: field.placeholder || '请输入内容...',
                            onChange(editor) {
                                // 同步内容到隐藏的 textarea
                                const html = editor.getHtml();
                                document.getElementById(fieldId).value = html;
                            },
                            MENU_CONF: {}
                        };
                        
                        // 配置上传图片（如果需要）
                        if (field.uploadImage !== false) {
                            editorConfig.MENU_CONF['uploadImage'] = {
                                server: API_BASE + (field.uploadUrl || '/api/admin/upload'),
                                fieldName: 'file',
                                headers: {
                                    'Authorization': getToken()
                                },
                                customInsert(res, insertFn) {
                                    if (res.code === 0) {
                                        insertFn(res.data.url, '', '');
                                    } else {
                                        layer.msg('图片上传失败：' + res.msg, { icon: 2 });
                                    }
                                }
                            };
                        }
                        
                        // 创建编辑器
                        const editor = createEditor({
                            selector: '#' + fieldId + '_editor',
                            html: value || '',
                            config: editorConfig,
                            mode: 'default'
                        });
                        
                        // 创建工具栏
                        const toolbarConfig = {
                            toolbarKeys: [
                                'headerSelect',
                                '|',
                                'bold',
                                'italic',
                                'underline',
                                'through',
                                '|',
                                'color',
                                'bgColor',
                                '|',
                                'fontSize',
                                'fontFamily',
                                'lineHeight',
                                '|',
                                'bulletedList',
                                'numberedList',
                                'todo',
                                '|',
                                'justifyLeft',
                                'justifyCenter',
                                'justifyRight',
                                'justifyJustify',
                                '|',
                                'emotion',
                                'insertLink',
                                'insertImage',
                                'insertTable',
                                'codeBlock',
                                '|',
                                'undo',
                                'redo',
                                '|',
                                'fullScreen'
                            ]
                        };
                        
                        // 创建工具栏容器（在编辑器上方）
                        const toolbarContainer = document.createElement('div');
                        toolbarContainer.id = fieldId + '_toolbar';
                        toolbarContainer.style.borderBottom = '1px solid #e8e8e8';
                        editorContainer.parentNode.insertBefore(toolbarContainer, editorContainer);
                        
                        const toolbar = createToolbar({
                            editor,
                            selector: '#' + fieldId + '_toolbar',
                            config: toolbarConfig,
                            mode: 'default'
                        });
                        
                        // 保存编辑器实例（用于销毁）
                        this.editors[fieldId] = editor;
                        
                    } catch (error) {
                        console.error('富文本编辑器初始化失败:', error);
                        // 降级为 textarea
                        const textarea = document.createElement('textarea');
                        textarea.name = field.name;
                        textarea.value = value || '';
                        textarea.className = 'layui-textarea';
                        textarea.rows = 10;
                        editorContainer.parentNode.replaceChild(textarea, editorContainer);
                    }
                }, 100);
            }
            
            // 颜色选择器（使用 layui 的 colorpicker，如果有）
            if (field.type === 'color') {
                if (layui.colorpicker) {
                    layui.colorpicker.render({
                        elem: '#' + fieldId,
                        color: value || '#000000'
                    });
                }
            }
            
            // 滑块
            if (field.type === 'slider') {
                if (layui.slider) {
                    layui.slider.render({
                        elem: '#' + fieldId,
                        min: field.min || 0,
                        max: field.max || 100,
                        value: value || (field.default || 0),
                        change: (val) => {
                            document.getElementById(fieldId + '_value').value = val;
                        }
                    });
                }
            }
            
            // 权限配置
            if (field.type === 'permissions') {
                // 获取权限选项
                request(this.config.api.permissions || '/api/admin/permissions', {
                    method: 'GET'
                }).then(res => {
                    if (res.code === 0) {
                        renderPermissions(fieldId, res.data, value);
                    }
                }).catch(err => {
                    console.error('加载权限选项失败：', err);
                    document.getElementById(fieldId).innerHTML = '<div style="color: red;">加载权限选项失败</div>';
                });
            }
        });
        
        // 监听所有 switch 开关的变化
        layui.use('form', function(){
            var form = layui.form;
            
            // 查找所有 switch 并监听
            document.querySelectorAll('input[lay-skin="switch"]').forEach(switchEl => {
                const fieldName = switchEl.dataset.fieldName;
                if (!fieldName) return;
                
                const filterName = 'switch-' + fieldName;
                
                form.on('switch(' + filterName + ')', function(data){
                    const checkedValue = data.elem.dataset.checkedValue || '1';
                    const uncheckedValue = data.elem.dataset.uncheckedValue || '0';
                    const valueInput = document.getElementById(data.elem.id + '_value');
                    if (valueInput) {
                        valueInput.value = data.elem.checked ? checkedValue : uncheckedValue;
                    }
                });
            });
            
            // 监听所有 checkbox 的变化 (用于更新 hidden input)
            form.on('checkbox', function(data){
                const filter = data.elem.getAttribute('lay-filter');
                if (filter && filter.startsWith('checkbox-')) {
                    const fieldName = filter.replace('checkbox-', '');
                    // 获取同一组所有选中的值
                    const checkedValues = [];
                    // 注意：这里的 querySelectorAll 范围是全局的，如果页面上有多个表单可能会有冲突
                    // 但 layer.open 是模态的，通常只关注顶层。更严谨的做法是在 form 内查找。
                    // data.elem.form 可以获取所属表单
                    const formEl = data.elem.form;
                    if (formEl) {
                        formEl.querySelectorAll(`input[lay-filter="${filter}"]:checked`).forEach(el => {
                            checkedValues.push(el.value);
                        });
                        
                        const hiddenInput = formEl.querySelector(`input[type="hidden"][name="${fieldName}"]`);
                        if (hiddenInput) {
                            hiddenInput.value = checkedValues.join(',');
                        }
                    }
                }
            });
        });

        // 初始化动态数据源 (Select, Radio, Checkbox)
        this.initDynamicDataSources(rowData);
    }

    /**
     * 初始化动态数据源 (Select, Radio, Checkbox)
     */
    initDynamicDataSources(rowData) {
        const form = layui.form;
        
        // 1. 处理 Select
        const selects = document.querySelectorAll('select[data-source-url]');
        selects.forEach(select => {
            const url = select.dataset.sourceUrl;
            const valueField = select.dataset.valueField;
            const labelField = select.dataset.labelField;
            const fieldName = select.name;
            // 获取当前选中的值（可能是编辑时的值）
            const currentValue = rowData ? rowData[fieldName] : (select.value === '加载中...' ? '' : select.value);
            
            request(url.replace(API_BASE, ''), { method: 'GET' }).then(res => {
                if (res.code === 0) {
                    let optionsHtml = '<option value="">请选择</option>';
                    const list = res.data.list || res.data; // 兼容 {code:0, data:[...]} 和 {code:0, data:{list:[...]}}
                    
                    if (Array.isArray(list)) {
                        list.forEach(item => {
                            const val = item[valueField];
                            const label = item[labelField];
                            const selected = val == currentValue ? 'selected' : '';
                            optionsHtml += `<option value="${val}" ${selected}>${label}</option>`;
                        });
                        
                        select.innerHTML = optionsHtml;
                        form.render('select'); // 重新渲染下拉框
                    }
                } else {
                    console.error('加载下拉数据失败:', res.msg);
                    select.innerHTML = '<option value="">加载失败</option>';
                    form.render('select');
                }
            });
        });
        
        // 2. 处理 Radio 和 Checkbox
        const dynamicContainers = document.querySelectorAll('.dynamic-source');
        dynamicContainers.forEach(container => {
            const url = container.dataset.sourceUrl;
            const type = container.dataset.type; // radio or checkbox
            const name = container.dataset.name;
            const valueField = container.dataset.valueField;
            const labelField = container.dataset.labelField;
            const currentValue = container.dataset.value;
            
            request(url.replace(API_BASE, ''), { method: 'GET' }).then(res => {
                if (res.code === 0) {
                    let html = '';
                    const list = res.data.list || res.data;
                    
                    if (Array.isArray(list)) {
                        list.forEach(item => {
                            const val = item[valueField];
                            const label = item[labelField];
                            
                            if (type === 'radio') {
                                const checked = val == currentValue ? 'checked' : '';
                                html += `<input type="radio" name="${name}" value="${val}" title="${label}" ${checked}>`;
                            } else if (type === 'checkbox') {
                                const currentValues = currentValue ? String(currentValue).split(',') : [];
                                const checked = currentValues.includes(String(val)) ? 'checked' : '';
                                html += `<input type="checkbox" name="${name}[]" value="${val}" title="${label}" lay-skin="primary" lay-filter="checkbox-${name}" ${checked}>`;
                            }
                        });
                        
                        container.innerHTML = html;
                        form.render(type);
                    }
                } else {
                    container.innerHTML = '<div class="layui-form-mid layui-word-aux" style="color:red!important">加载失败</div>';
                }
            });
        });
    }
    
    /**
     * 提交表单
     */
    submitForm(data, isEdit, id, layerIndex) {
        const layer = layui.layer;
        const loadIndex = layer.load(2, { shade: 0.3 });
        
        let url = this.config.api[isEdit ? 'edit' : 'add'];
        if (isEdit) {
            url = url.replace('{id}', id);
        }
        
        request(url, {
            method: 'POST',
            data: data
        }).then(res => {
            layer.close(loadIndex);
            if (res.code === 0) {
                layer.msg(res.msg, { icon: 1 });
                layer.close(layerIndex);
                this.tableIns.reload();
            } else {
                layer.msg(res.msg, { icon: 2 });
            }
        });
    }
    
    /**
     * 删除行
     */
    deleteRow(data, obj) {
        const layer = layui.layer;
        
        layer.confirm('确定删除吗？', { icon: 3 }, () => {
            const loadIndex = layer.load(2, { shade: 0.3 });
            
            let url = this.config.api.delete.replace('{id}', data.id);
            
            request(url, {
                method: 'DELETE'
            }).then(res => {
                layer.close(loadIndex);
                if (res.code === 0) {
                    obj.del();
                    layer.msg('删除成功', { icon: 1 });
                } else {
                    layer.msg(res.msg, { icon: 2 });
                }
            });
        });
    }
    
    /**
     * 渲染纯表单页面
     */
    renderFormPage() {
        const tips = this.config.tips;
        let tipsHtml = '';
        
        if (tips) {
            tipsHtml = `
                <div style="background: #ecf5ff; border: 1px solid #d9ecff; border-radius: 4px; 
                            padding: 15px; margin-bottom: 20px; color: #409eff; font-size: 14px;">
                    <i class="layui-icon layui-icon-tips"></i>
                    ${tips.text}
                </div>
            `;
        }
        
        const html = `
            <div style="max-width: 600px; margin: 20px auto;">
                <div class="card">
                    <div class="card-title">
                        <i class="layui-icon ${this.config.page.icon}"></i>
                        ${this.config.page.title}
                    </div>
                    
                    ${tipsHtml}
                    
                    <form class="layui-form" lay-filter="formPage" id="formPage">
                        ${this.renderFormFields()}
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn layui-btn-normal" lay-submit lay-filter="submitFormPage">
                                    <i class="layui-icon layui-icon-ok"></i>
                                    提交
                                </button>
                                <button type="reset" class="layui-btn layui-btn-primary">
                                    <i class="layui-icon layui-icon-refresh"></i>
                                    重置
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        this.container.innerHTML = html;
        
        // 渲染表单
        layui.use(['form', 'layer', 'laydate', 'upload'], () => {
            const form = layui.form;
            const layer = layui.layer;
            
            // 初始化组件
            this.initFormComponents(null, false);
            
            // 自定义验证
            this.registerFormValidators(form);
            
            form.render();
            
            // 提交
            form.on('submit(submitFormPage)', (data) => {
                const loadIndex = layer.load(2, { shade: 0.3 });
                
                request(this.config.api.submit, {
                    method: 'POST',
                    data: data.field
                }).then(res => {
                    layer.close(loadIndex);
                    if (res.code === 0) {
                        layer.msg(res.msg, { icon: 1 }, () => {
                            // 修改密码成功后跳转登录
                            if (this.config.api.submit.includes('change-password')) {
                                clearToken();
                                location.href = 'login.html';
                            }
                        });
                    } else {
                        layer.msg(res.msg, { icon: 2 });
                    }
                });
                
                return false;
            });
        });
    }
    
    /**
     * 检查权限
     * @param {string} permission 权限标识 (e.g. 'update', 'delete')
     * @returns {boolean}
     */
    checkPermission(permission) {
        // 1. 优先检查是否为超级管理员 (Fail Closed 策略)
        const isSuperAdmin = localStorage.getItem('is_super_admin') === '1';
        if (isSuperAdmin) {
            return true; 
        }

        // 2. 获取权限数据
        const userPermissionsStr = localStorage.getItem('admin_permissions');
        
        // 如果不是超管，且没有权限数据，默认拒绝所有操作 (安全！)
        if (!userPermissionsStr) {
            // console.warn('权限检查: 非超管且无权限数据，默认拒绝');
            return false; 
        }
        
        try {
            const allPermissions = JSON.parse(userPermissionsStr);
            // 获取当前模块名
            const module = this.config.page.page || ''; 
            
            if (!module) {
                console.warn('权限检查: 页面未配置 page 标识，无法判断权限，默认拒绝');
                return false;
            }
            
            const modulePerms = allPermissions[module];
            
            // 如果该模块在权限列表中不存在，说明用户没有该模块的任何权限
            if (!modulePerms) {
                return false; 
            }
            
            return modulePerms.includes(permission);
        } catch (e) {
            console.error('权限解析失败', e);
            return false; // 出错也默认拒绝
        }
    }

    /**
     * 注册表单验证器
     */
    registerFormValidators(form) {
        form.verify({
            password: function(value) {
                if (value.length < 6) {
                    return '密码长度不能少于6位';
                }
            },
            confirmPassword: function(value) {
                const newPassword = document.querySelector('input[name=new_password]').value;
                if (value !== newPassword) {
                    return '两次输入的密码不一致';
                }
            }
        });
    }
}

// 全局渲染函数
window.renderCrudPage = function(config, containerId) {
    const renderer = new CrudRenderer(config, containerId);
    renderer.render();
    return renderer;
}

/**
 * 渲染权限配置界面
 * 
 * @param {string} containerId 容器ID
 * @param {object} allPermissions 所有权限配置
 * @param {string} currentValue 当前权限值（JSON字符串）
 */
function renderPermissions(containerId, allPermissions, currentValue) {
    const container = document.getElementById(containerId);
    const valueInput = document.getElementById(containerId + '_value');
    // 生成唯一 filter ID 防止事件冲突
    const filterId = 'perm_change_' + Math.floor(Math.random() * 100000);
    
    // 解析当前权限 (State)
    let currentPermissions = {};
    try {
        if (currentValue) {
            if (typeof currentValue === 'string') {
                currentPermissions = JSON.parse(currentValue);
            } else if (typeof currentValue === 'object') {
                currentPermissions = currentValue;
            }
        }
    } catch (e) {
        console.error('解析权限配置失败：', e);
    }
    
    // 确保初始值写入隐藏域
    valueInput.value = JSON.stringify(currentPermissions);
    
    // 渲染权限选择界面
    let html = '<div class="permissions-container" style="max-height: 400px; overflow-y: auto;">';
    
    Object.keys(allPermissions).forEach(module => {
        const perm = allPermissions[module];
        const modulePermissions = currentPermissions[module] || [];
        
        const isViewType = perm.type === 'view';
        const moduleIcon = isViewType ? 'layui-icon-template' : 'layui-icon-app';
        const moduleTag  = isViewType
            ? `<span style="background:#1890ff;color:#fff;font-size:10px;padding:1px 6px;border-radius:3px;margin-left:6px;vertical-align:middle;">视图</span>`
            : '';

        html += `
            <div class="permission-module" style="margin-bottom: 20px; padding: 15px; background: #f8f8f8; border-radius: 4px;">
                <div style="font-weight: 600; margin-bottom: 10px; color: #333;">
                    <i class="layui-icon ${moduleIcon}"></i> ${perm.name}${moduleTag}
                </div>
                <div class="permission-actions">
        `;
        
        Object.keys(perm.actions).forEach(action => {
            const checked = modulePermissions.includes(action) ? 'checked' : '';
            const actionLabel = perm.actions[action];
            
            html += `
                <div style="display: inline-block; margin-right: 15px; margin-bottom: 5px;">
                    <input type="checkbox" 
                           name="perm_${module}_${action}" 
                           data-module="${module}" 
                           data-action="${action}"
                           ${checked}
                           lay-skin="primary"
                           lay-filter="${filterId}"
                           title="${actionLabel}">
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    container.innerHTML = html;
    
    // 监听复选框变化 (使用 State 维护，不再依赖 DOM 查询)
    layui.use('form', function(){
        var form = layui.form;
        form.render('checkbox');
        
        form.on(`checkbox(${filterId})`, function(data){
            const module = data.elem.dataset.module;
            const action = data.elem.dataset.action;
            const isChecked = data.elem.checked;
            
            if (!currentPermissions[module]) {
                currentPermissions[module] = [];
            }
            
            if (isChecked) {
                if (!currentPermissions[module].includes(action)) {
                    currentPermissions[module].push(action);
                }
            } else {
                currentPermissions[module] = currentPermissions[module].filter(a => a !== action);
                if (currentPermissions[module].length === 0) {
                    delete currentPermissions[module];
                }
            }
            
            valueInput.value = JSON.stringify(currentPermissions);
        });
    });
}
