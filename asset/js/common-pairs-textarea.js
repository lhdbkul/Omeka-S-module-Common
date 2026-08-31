/**
 * Editor of pairs "key = value".
 *
 * Two layers:
 * - CommonPairsEditor.create(): a reusable list of rows (key, value), sortable,
 *   with a picker of known keys, usable by any script (see FieldsTextarea);
 * - the binding to the textareas flagged by
 * Common\Form\Element\TraitPairsEditor:
 *   the lines of the textarea are parsed into rows and serialized back on each
 *   change, so the textarea remains the posted value.
 */

(function () {
    'use strict';

    const config = window.CommonPairsTextarea || {};
    const labels = config.labels || {};
    const t = function (key, fallback) {
        return labels[key] || fallback;
    };

    const escapeHtml = function (s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    };

    // ---- Core editor -----------------------------------------------------

    const defaultEditorOptions = {
        keyLabel: '',
        valueLabel: '',
        // "text" or "number"; "none" hides the value column (simple list).
        valueType: 'text',
        sortable: true,
        // Known keys: {key: default value or label}.
        keys: {},
        // A css selector of a select whose options complete the known keys.
        keySource: '',
        keySkip: [],
        // Fill the value with the default of the key when picked.
        keyFill: true,
        // The user can type any key.
        freeKeys: true,
        keyReadonly: false,
        // A string forbidden in the keys (the separator of the textarea).
        keyForbidden: '',
        // Show the header with the labels of the columns.
        header: true,
        // Show the picker and the "+" button.
        actions: true,
        // Callback on any change.
        onChange: null,
    };

    // The known keys: the static list, completed by the options of the source
    // select at call time (its options may be built by the page).
    const knownKeys = function (options) {
        const keys = Object.assign({}, options.keys);
        if (options.keySource) {
            const source = document.querySelector(options.keySource);
            if (source) {
                Array.from(source.querySelectorAll('option')).forEach(function (opt) {
                    if (opt.value === '' || opt.disabled) return;
                    if (options.keySkip.indexOf(opt.value) !== -1) return;
                    if (!(opt.value in keys)) keys[opt.value] = opt.textContent.trim();
                });
            }
        }
        return keys;
    };

    /**
     * Create an editor of pairs in a mount element.
     *
     * @return {object} {element, list, getRows, setRows, addRow, count}
     */
    const createEditor = function (mount, userOptions, initialRows) {
        const options = Object.assign({}, defaultEditorOptions, userOptions || {});
        options.keyLabel = options.keyLabel || t('key', 'key');
        options.valueLabel = options.valueLabel || t('value', 'value');
        const isList = options.valueType === 'none';

        const element = document.createElement('div');
        element.className = 'common-pairs';
        mount.appendChild(element);

        const table = document.createElement('div');
        table.className = 'common-pairs-rows';
        if (options.header) {
            table.innerHTML = '<div class="common-pairs-head">'
                + (options.sortable ? '<span class="common-pairs-cell-handle"></span>' : '')
                + '<span class="common-pairs-cell-key">' + escapeHtml(options.keyLabel) + '</span>'
                + (isList ? '' : '<span class="common-pairs-cell-value">' + escapeHtml(options.valueLabel) + '</span>')
                + '<span class="common-pairs-cell-remove"></span>'
                + '</div>';
        }
        const list = document.createElement('div');
        list.className = 'common-pairs-list';
        table.appendChild(list);
        element.appendChild(table);

        const actions = document.createElement('div');
        actions.className = 'common-pairs-actions';
        element.appendChild(actions);

        let picker = null;
        const hasKeys = !!options.keySource || Object.keys(options.keys).length > 0;
        if (options.actions && hasKeys) {
            picker = document.createElement('select');
            picker.className = 'common-pairs-picker';
            picker.setAttribute('aria-label', t('pick', 'Add…'));
            actions.appendChild(picker);
        }
        let addButton = null;
        if (options.actions && options.freeKeys) {
            addButton = document.createElement('button');
            addButton.type = 'button';
            addButton.className = 'common-pairs-add o-icon-add button';
            addButton.title = t('add', 'Add');
            addButton.setAttribute('aria-label', t('add', 'Add'));
            actions.appendChild(addButton);
        }
        if (!picker && !addButton) actions.hidden = true;

        const getRows = function () {
            return Array.from(list.children).map(function (row) {
                return {
                    key: row.querySelector('.common-pairs-key').value,
                    value: isList ? '' : row.querySelector('.common-pairs-value').value,
                };
            });
        };

        const refreshPicker = function () {
            if (!picker) return;
            const keys = knownKeys(options);
            const used = getRows().map(function (r) { return r.key.trim(); });
            picker.innerHTML = '';
            const first = document.createElement('option');
            first.value = '';
            first.textContent = t('pick', 'Add…');
            picker.appendChild(first);
            Object.keys(keys).forEach(function (key) {
                if (used.indexOf(key) !== -1) return;
                const opt = document.createElement('option');
                opt.value = key;
                opt.textContent = keys[key] && keys[key] !== key ? keys[key] + ' (' + key + ')' : key;
                picker.appendChild(opt);
            });
            picker.disabled = picker.options.length <= 1;
        };

        const changed = function () {
            refreshPicker();
            if (options.onChange) options.onChange(getRows());
        };

        const makeRow = function (pair) {
            const row = document.createElement('div');
            row.className = 'common-pairs-row';
            if (options.sortable) {
                row.innerHTML = '<span class="common-pairs-cell-handle sortable-handle" title="' + escapeHtml(t('drag', 'Drag to reorder')) + '"></span>';
            }
            const key = document.createElement('input');
            key.type = 'text';
            key.className = 'common-pairs-key common-pairs-cell-key';
            key.value = pair.key || '';
            key.setAttribute('aria-label', options.keyLabel);
            if (options.keyReadonly) key.readOnly = true;
            row.appendChild(key);
            if (!isList) {
                const value = document.createElement('input');
                value.type = options.valueType === 'number' ? 'number' : 'text';
                if (options.valueType === 'number') value.step = 'any';
                value.className = 'common-pairs-value common-pairs-cell-value';
                value.value = pair.value || '';
                value.setAttribute('aria-label', options.valueLabel);
                const keys = knownKeys(options);
                if (options.keyFill && keys[pair.key] && keys[pair.key] !== pair.key) value.placeholder = keys[pair.key];
                row.appendChild(value);
            }
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'common-pairs-remove common-pairs-cell-remove o-icon-delete';
            remove.title = t('remove', 'Remove');
            remove.setAttribute('aria-label', t('remove', 'Remove'));
            row.appendChild(remove);
            return row;
        };

        const addRow = function (pair, focus) {
            pair = pair || {key: '', value: ''};
            const row = makeRow(pair);
            list.appendChild(row);
            if (focus) {
                const input = row.querySelector(pair.key ? '.common-pairs-value' : '.common-pairs-key') || row.querySelector('input');
                if (input) input.focus();
            }
            changed();
            return row;
        };

        const setRows = function (rows) {
            list.innerHTML = '';
            (rows || []).forEach(function (pair) { list.appendChild(makeRow(pair)); });
            refreshPicker();
        };

        if (addButton) {
            addButton.addEventListener('click', function () {
                addRow(null, true);
            });
        }
        if (picker) {
            picker.addEventListener('change', function () {
                const key = picker.value;
                if (!key) return;
                const keys = knownKeys(options);
                addRow({key: key, value: options.keyFill && !isList && keys[key] !== key ? keys[key] : ''}, false);
                picker.value = '';
            });
        }
        // The separator of the textarea cannot be part of a key (it is split
        // at its first occurrence), but a value may contain it.
        const checkKey = function (input) {
            if (!options.keyForbidden) return;
            input.setCustomValidity(input.value.indexOf(options.keyForbidden) === -1
                ? ''
                : t('keyForbidden', 'The key cannot contain "{separator}".').replace('{separator}', options.keyForbidden));
            input.reportValidity();
        };
        list.addEventListener('input', function (e) {
            if (e.target.classList.contains('common-pairs-key')) checkKey(e.target);
            changed();
        });
        list.addEventListener('click', function (e) {
            const remove = e.target.closest('.common-pairs-remove');
            if (!remove) return;
            remove.closest('.common-pairs-row').remove();
            changed();
        });
        list.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' || !e.target.matches('input')) return;
            e.preventDefault();
            addRow(null, true);
        });

        // Reorder by drag and drop of the handle. The events are stopped: the
        // editor may be nested in another sortable list.
        if (options.sortable) {
            let dragged = null;
            list.addEventListener('mousedown', function (e) {
                const row = e.target.closest('.common-pairs-row');
                if (row) row.draggable = !!e.target.closest('.common-pairs-cell-handle');
            });
            list.addEventListener('dragstart', function (e) {
                const row = e.target.closest('.common-pairs-row');
                if (!row || !row.draggable) {
                    e.preventDefault();
                    return;
                }
                e.stopPropagation();
                dragged = row;
                row.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', ''); } catch (err) {}
            });
            list.addEventListener('dragover', function (e) {
                if (!dragged) return;
                e.preventDefault();
                e.stopPropagation();
                const over = e.target.closest('.common-pairs-row');
                if (!over || over === dragged) return;
                const rect = over.getBoundingClientRect();
                const before = e.clientY < rect.top + rect.height / 2;
                list.insertBefore(dragged, before ? over : over.nextSibling);
            });
            list.addEventListener('dragend', function (e) {
                if (!dragged) return;
                e.stopPropagation();
                dragged.classList.remove('dragging');
                dragged.draggable = false;
                dragged = null;
                changed();
            });
        }

        setRows(initialRows || []);

        return {
            element: element,
            list: list,
            getRows: getRows,
            setRows: setRows,
            addRow: addRow,
            count: function () { return list.children.length; },
        };
    };

    // ---- Formats of the textarea -----------------------------------------

    const unquote = function (v) {
        v = v.trim();
        if (v.length >= 2 && ((v[0] === '"' && v[v.length - 1] === '"') || (v[0] === "'" && v[v.length - 1] === "'"))) {
            return v.substring(1, v.length - 1);
        }
        return v;
    };

    const isRawIniValue = function (v) {
        return /^-?\d+(\.\d+)?$/.test(v) || /^(true|false|null|on|off|yes|no)$/i.test(v);
    };

    // Parse the text into rows, or return null when a line cannot be displayed
    // as a row (ini sections, comments, invalid lines).
    const parse = function (text, options) {
        const rows = [];
        const lines = text.split(/\r?\n/);
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            if (!line.trim()) continue;
            if (options.format === 'list') {
                rows.push({key: line.trim(), value: ''});
                continue;
            }
            if (options.format === 'ini' && /^\s*[;#\[]/.test(line)) return null;
            const pos = line.indexOf(options.separator);
            if (pos === -1) {
                if (options.format === 'ini') return null;
                rows.push({key: line.trim(), value: ''});
                continue;
            }
            const key = line.substring(0, pos).trim();
            let value = line.substring(pos + options.separator.length).trim();
            if (options.format === 'ini') value = unquote(value);
            rows.push({key: key, value: value});
        }
        return rows;
    };

    const serialize = function (rows, options) {
        const lines = [];
        rows.forEach(function (row) {
            const key = row.key.trim();
            let value = row.value.trim();
            if (options.format === 'list') {
                if (key !== '') lines.push(key);
                return;
            }
            if (key === '' && value === '') return;
            if (options.format === 'ini') {
                if (!isRawIniValue(value)) value = '"' + value + '"';
                lines.push(key + ' ' + options.separator + ' ' + value);
                return;
            }
            lines.push(value === '' ? key : key + ' ' + options.separator + ' ' + value);
        });
        return lines.length ? lines.join('\n') + '\n' : '';
    };

    // ---- Binding to a textarea -------------------------------------------

    const readOptions = function (textarea) {
        const d = textarea.dataset;
        let keys = {};
        if (d.pairsKeys) {
            try { keys = JSON.parse(d.pairsKeys) || {}; } catch (e) { keys = {}; }
        }
        let skip = [];
        if (d.pairsKeySkip) {
            try { skip = JSON.parse(d.pairsKeySkip) || []; } catch (e) { skip = []; }
        }
        const format = d.pairsFormat || 'lines';
        return {
            format: format,
            separator: d.pairsSeparator || '=',
            defaultDisplay: d.pairsDefaultDisplay === 'text' ? 'text' : 'form',
            editor: {
                keyLabel: d.pairsKeyLabel || '',
                valueLabel: d.pairsValueLabel || '',
                valueType: format === 'list' ? 'none' : (d.pairsValueType === 'number' ? 'number' : 'text'),
                sortable: d.pairsSortable !== '0',
                keyFill: d.pairsKeyFill === '1',
                freeKeys: d.pairsFreeKeys !== '0',
                keyReadonly: d.pairsKeyReadonly === '1',
                keyForbidden: format === 'list' ? '' : (d.pairsSeparator || '='),
                keys: keys,
                keySource: d.pairsKeySource || '',
                keySkip: skip,
            },
        };
    };

    const bindTextarea = function (textarea) {
        if (textarea.dataset.pairsReady) return;
        textarea.dataset.pairsReady = '1';
        const options = readOptions(textarea);

        const wrapper = document.createElement('div');
        wrapper.className = 'common-pairs-wrapper';
        textarea.parentNode.insertBefore(wrapper, textarea);

        let syncing = false;
        const editor = createEditor(wrapper, Object.assign({
            onChange: function (rows) {
                syncing = true;
                textarea.value = serialize(rows, options);
                syncing = false;
            },
        }, options.editor));

        // The textarea goes between the rows and the actions, where the
        // toggle form/text is added on the right.
        const table = editor.element.querySelector('.common-pairs-rows');
        const actions = editor.element.querySelector('.common-pairs-actions');
        editor.element.insertBefore(textarea, actions);
        actions.hidden = false;
        const notice = document.createElement('span');
        notice.className = 'common-pairs-notice';
        notice.hidden = true;
        notice.textContent = t('unparsable', 'The text cannot be edited as a list: fix it or edit it as text.');
        actions.appendChild(notice);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'common-pairs-toggle button';
        actions.appendChild(toggle);
        const formOnly = Array.from(actions.querySelectorAll('.common-pairs-picker, .common-pairs-add'));

        const build = function () {
            const rows = parse(textarea.value, options);
            if (rows === null) return false;
            editor.setRows(rows);
            return true;
        };

        let formMode = false;
        const setMode = function (form) {
            if (form && !build()) {
                notice.hidden = false;
                form = false;
            } else {
                notice.hidden = true;
            }
            formMode = form;
            table.hidden = !form;
            formOnly.forEach(function (el) { el.hidden = !form; });
            textarea.hidden = form;
            toggle.textContent = form ? t('editAsText', 'Edit as text') : t('editAsForm', 'Edit as a form');
        };

        toggle.addEventListener('click', function () {
            setMode(!formMode);
        });
        // The textarea may be filled by another script: rebuild the rows.
        textarea.addEventListener('input', function () {
            if (formMode && !syncing) build();
        });

        setMode(options.defaultDisplay === 'form');
    };

    const initAll = function (root) {
        (root || document).querySelectorAll('textarea.common-pairs-textarea').forEach(bindTextarea);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(); });
    } else {
        initAll();
    }

    // Textareas added later (collections, sidebars).
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                if (n.matches && n.matches('textarea.common-pairs-textarea')) bindTextarea(n);
                else initAll(n);
            });
        });
    });
    observer.observe(document.documentElement, {childList: true, subtree: true});

    window.CommonPairsEditor = {
        create: createEditor,
        bind: bindTextarea,
        init: initAll,
    };
})();
