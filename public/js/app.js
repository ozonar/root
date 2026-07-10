/* ==========================================
   Checker App - Shared JavaScript
   ========================================== */

// ==========================================
// Relative time formatting (dayjs)
// ==========================================

function formatRelativeTime(dateStr) {
    var date = dayjs(dateStr);
    var now = dayjs();
    var diffMinutes = now.diff(date, 'minute');
    var diffHours = now.diff(date, 'hour');
    var diffDays = now.diff(date, 'day');
    var diffMonths = now.diff(date, 'month');
    var diffYears = now.diff(date, 'year');

    var relative;
    if (diffMinutes < 1) {
        relative = 'только что';
    } else if (diffMinutes < 60) {
        relative = date.fromNow();
    } else if (diffHours < 24) {
        relative = date.fromNow();
    } else if (diffDays < 7) {
        relative = date.fromNow();
    } else if (diffMonths < 1) {
        relative = diffDays + ' ' + pluralize(diffDays, ['день', 'дня', 'дней']) + ' назад';
    } else if (diffYears < 1) {
        relative = date.fromNow();
    } else {
        relative = date.fromNow();
    }

    return relative;
}

function pluralize(count, forms) {
    var mod100 = count % 100;
    var mod10 = count % 10;
    if (mod100 >= 11 && mod100 <= 19) return forms[2];
    if (mod10 === 1) return forms[0];
    if (mod10 >= 2 && mod10 <= 4) return forms[1];
    return forms[2];
}

function formatTooltipDate(dateStr) {
    return dayjs(dateStr).format('D MMMM YYYY, HH:mm');
}

const API_BASE = '/api';
let currentTaskId = null;
let statuses = [];
let saveTimers = {};

// ==========================================
// API Helper
// ==========================================

function apiRequest(path, method, data) {
    if (method === undefined) method = 'GET';
    if (data === undefined) data = null;
    const token = localStorage.getItem('auth_token');
    const headers = { 'Content-Type': 'application/json' };
    if (token) {
        headers['X-Auth-Token'] = token;
    }
    return fetch(API_BASE + path, {
        method: method,
        headers: headers,
        body: data ? JSON.stringify(data) : null
    }).then(function(response) {
        if (!response.ok) {
            return response.json().then(function(err) {
                throw { status: response.status, data: err };
            }).catch(function(e) {
                if (e && e.status) throw e;
                throw { status: response.status, data: {} };
            });
        }
        return response.json();
    });
}

// ==========================================
// Debounced save (1 second default)
// ==========================================

function debouncedSave(key, fn, delay) {
    if (saveTimers[key]) clearTimeout(saveTimers[key]);
    saveTimers[key] = setTimeout(fn, delay || 1000);
}

// ==========================================
// Reload page data from server
// ==========================================

function reloadPage(pageId) {
    apiRequest('/pages/' + pageId).then(function(response) {
        statuses = response.statuses || [];
        window.reloadTasks(pageId, response.tasks || []);
    });
}

function addNewTask(pageId) {
    apiRequest('/pages/' + pageId + '/tasks', 'POST', {
        text: '', parentId: null, order: 999
    }).then(function() {
        reloadPage(pageId);
    });
}

// Default reloadTasks — finds the card in grid or updates tasks-container
window.reloadTasks = function(pageId, tasks) {
    var card = document.querySelector('.page-card[data-page-id="' + pageId + '"]');
    if (card) {
        var editorContainer = card.querySelector('.page-card-editor');
        if (editorContainer) {
            renderInlineTasks(tasks || [], editorContainer, pageId);
        }
    } else {
        var container = document.getElementById('tasks-container');
        if (container) {
            renderInlineTasks(tasks || [], container, pageId);
        }
    }
};

// ==========================================
// Tasks Tree (universal)
// options: { pageId, onToggleStatus, onAddChild, onDelete, onAddAfter }
// All callbacks receive (taskId) or (taskId, completed).
// ==========================================

function renderTaskItem(task, level, taskList, options) {
    var children = taskList.filter(function(t) { return t.parentId === task.id; });
    var hasChildren = children.length > 0;
    var isCompleted = task.status === 'finished';
    var isHighPriority = task.isPriority;

    var div = document.createElement('div');
    div.className = 'task-item';
    div.dataset.taskId = task.id;
    div.dataset.level = level;
    div.dataset.parentId = task.parentId !== undefined && task.parentId !== null ? task.parentId : '';
    div.dataset.order = task.order;
    div.dataset.pageId = options.pageId;

    var row = document.createElement('div');
    row.className = 'task-row';

    // Drag handle
    var dragHandle = document.createElement('span');
    dragHandle.className = 'drag-handle';
    dragHandle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
    row.appendChild(dragHandle);

    // Checkbox
    var checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'task-checkbox';
    if (isCompleted) checkbox.checked = true;
    checkbox.addEventListener('change', function() {
        options.onToggleStatus(task.id, checkbox.checked);
    });
    row.appendChild(checkbox);

    // Priority icon
    var prioritySpan = document.createElement('span');
    prioritySpan.className = 'task-priority' + (isHighPriority ? ' high' : '');
    prioritySpan.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
    row.appendChild(prioritySpan);

    // Text
    var text = document.createElement('div');
    text.className = 'task-text' + (isCompleted ? ' completed' : '');
    text.contentEditable = 'true';
    text.innerHTML = task.text || '';
    row.appendChild(text);

    // Status badge
    if (task.status && task.status !== 'processed') {
        var badge = document.createElement('span');
        badge.className = 'task-status-badge show';
        badge.textContent = task.statusName || task.status;
        row.appendChild(badge);
    }

    // Actions
    var actions = document.createElement('div');
    actions.className = 'task-actions';

    var addChildBtn = document.createElement('button');
    addChildBtn.className = 'add-child-btn';
    addChildBtn.title = 'Добавить подзадачу';
    addChildBtn.innerHTML = '<i class="fas fa-plus"></i>';
    addChildBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        options.onAddChild(task.id);
    });
    actions.appendChild(addChildBtn);

    var deleteBtn = document.createElement('button');
    deleteBtn.className = 'delete-task-btn';
    deleteBtn.title = isCompleted ? 'Удалить' : 'Завершить';
    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
    deleteBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        handleTaskDeleteOrFinish(task.id, isCompleted, options);
    });
    actions.appendChild(deleteBtn);

    row.appendChild(actions);
    div.appendChild(row);

    // Children
    if (hasChildren) {
        var childrenDiv = document.createElement('div');
        childrenDiv.className = 'task-children';
        children.forEach(function(child) {
            childrenDiv.appendChild(renderTaskItem(child, level + 1, taskList, options));
        });
        div.appendChild(childrenDiv);
    }

    // Auto-save text on input with debounce
    text.addEventListener('input', function() {
        var taskId = task.id;
        var html = text.innerHTML;
        debouncedSave('task-text-' + taskId, function() {
            apiRequest('/tasks/' + taskId, 'PUT', { text: html });
        });
    });

    text.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            var html = text.innerHTML;
            var textContent = text.textContent.trim();
            if (textContent) {
                apiRequest('/tasks/' + task.id, 'PUT', { text: html });
            }
            options.onAddAfter(task.id);
        }
        if (e.key === 'Tab') {
            e.preventDefault();
            if (e.shiftKey) {
                outdentTask(task.id);
            } else {
                indentTask(task.id);
            }
        }
        if ((e.key === 'Delete' || e.key === 'Backspace') && !e.shiftKey && !e.ctrlKey && !e.metaKey) {
            var txt = text.textContent.trim();
            var html = text.innerHTML;
            if (txt === '' && (html === '' || html === '<br>' || html === '<br/>')) {
                e.preventDefault();
                handleTaskDeleteOrFinish(task.id, isCompleted, options);
            }
        }
    });

    row.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        currentTaskId = task.id;
        showContextMenu(e.pageX, e.pageY, task);
    });

    row.addEventListener('dblclick', function() {
        openTaskModal(task.id);
    });

    return div;
}

// ==========================================
// Inline Tasks (for pages grid on main page)
// ==========================================

function renderInlineTasks(pageTasks, container, pageId) {
    var options = {
        pageId: pageId,
        onToggleStatus: function(taskId, completed) {
            var newStatus = completed ? 'finished' : 'processed';
            apiRequest('/tasks/' + taskId + '/status', 'PUT', { status: newStatus }).then(function() {
                reloadPage(pageId);
            });
        },
        onAddChild: function(parentId) {
            apiRequest('/pages/' + pageId + '/tasks', 'POST', {
                text: '', parentId: parentId, order: 0
            }).then(function() {
                reloadPage(pageId);
            });
        },
        onDelete: function(taskId) {
            apiRequest('/tasks/' + taskId, 'DELETE').then(function() {
                reloadPage(pageId);
            });
        },
        onAddAfter: function(taskId) {
            apiRequest('/pages/' + pageId + '/tasks', 'POST', {
                text: '', parentId: null, order: 999
            }).then(function() {
                reloadPage(pageId);
            });
        }
    };

    container.innerHTML = '';

    if (pageTasks.length === 0) {
        container.innerHTML = '<div class="empty-tasks-bottom"><button class="btn empty-add-task-btn" onclick="addNewTask(' + pageId + ')"><i class="fas fa-plus"></i> Задача</button></div>';
        return;
    }

    var rootTasks = pageTasks.filter(function(t) { return !t.parentId; });
    rootTasks.forEach(function(task) {
        container.appendChild(renderTaskItem(task, 0, pageTasks, options));
    });

    makeSortable();
}

// ==========================================
// Indent / Outdent (based on DOM data attributes)
// ==========================================

function indentTask(taskId) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    if (!el) return;
    var parentId = el.dataset.parentId !== '' ? parseInt(el.dataset.parentId) : null;
    var order = parseInt(el.dataset.order);

    var siblings = document.querySelectorAll('.task-item[data-parent-id="' + (parentId !== null ? parentId : '') + '"]');
    var prevEl = null;
    siblings.forEach(function(s) {
        var sOrder = parseInt(s.dataset.order);
        if (sOrder < order) {
            if (!prevEl || parseInt(prevEl.dataset.order) < sOrder) {
                prevEl = s;
            }
        }
    });

    if (prevEl) {
        var prevId = parseInt(prevEl.dataset.taskId);
        var childrenCount = document.querySelectorAll('.task-item[data-parent-id="' + prevId + '"]').length;
        moveTask(taskId, prevId, childrenCount);
    }
}

function outdentTask(taskId) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    if (!el) return;
    var parentId = el.dataset.parentId !== '' ? parseInt(el.dataset.parentId) : null;
    if (parentId === null) return;

    var parentEl = document.querySelector('.task-item[data-task-id="' + parentId + '"]');
    if (parentEl) {
        var grandParentId = parentEl.dataset.parentId !== '' ? parseInt(parentEl.dataset.parentId) : null;
        var parentOrder = parseInt(parentEl.dataset.order);
        moveTask(taskId, grandParentId, parentOrder + 1);
    } else {
        var rootCount = document.querySelectorAll('.task-item[data-parent-id=""]').length;
        moveTask(taskId, null, rootCount);
    }
}

// ==========================================
// Drag and Drop
// ==========================================

function makeSortable() {
    var items = document.querySelectorAll('.task-item');
    items.forEach(function(item) {
        var handle = item.querySelector('.drag-handle');
        if (handle) {
            handle.removeEventListener('mousedown', handle._dragStart);
            handle._dragStart = function(e) { dragStart(e, item); };
            handle.addEventListener('mousedown', handle._dragStart);
        }
    });
}

function dragStart(e, taskItem) {
    e.preventDefault();
    var taskId = parseInt(taskItem.dataset.taskId);
    var startY = e.pageY;
    var isDragging = false;
    var clone = null;
    var dropTarget = null;
    var dropPosition = 'after';

    function onMouseMove(e) {
        if (!isDragging && Math.abs(e.pageY - startY) > 5) {
            isDragging = true;
            taskItem.classList.add('dragging');
            clone = taskItem.cloneNode(true);
            var rect = taskItem.getBoundingClientRect();
            clone.style.position = 'fixed';
            clone.style.pointerEvents = 'none';
            clone.style.opacity = '0.8';
            clone.style.width = rect.width + 'px';
            clone.style.zIndex = '9999';
            clone.style.background = 'white';
            clone.style.boxShadow = '0 10px 40px rgba(0,0,0,0.15)';
            clone.style.borderRadius = '8px';
            clone.style.padding = '8px';
            document.body.appendChild(clone);
        }
        if (isDragging && clone) {
            var taskItemRect = taskItem.getBoundingClientRect();
            clone.style.top = (e.pageY - 20) + 'px';
            clone.style.left = taskItemRect.left + 'px';

            document.querySelectorAll('.task-item').forEach(function(el) {
                el.classList.remove('drag-over');
            });

            var elemBelow = document.elementFromPoint(e.clientX, e.clientY);
            if (elemBelow) {
                var target = elemBelow.closest('.task-item');
                if (target && parseInt(target.dataset.taskId) !== taskId) {
                    var targetRect = target.getBoundingClientRect();
                    var targetMiddle = targetRect.top + targetRect.height / 2;
                    dropPosition = e.pageY < targetMiddle ? 'before' : 'after';
                    dropTarget = target;

                    if (dropPosition === 'before') {
                        target.classList.add('drag-over');
                    } else {
                        var next = target.nextElementSibling;
                        if (next && next.classList.contains('task-item')) {
                            next.classList.add('drag-over');
                        }
                    }
                }
            }
        }
    }

    function onMouseUp(e) {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        taskItem.classList.remove('dragging');
        if (clone && clone.parentNode) clone.parentNode.removeChild(clone);

        if (dropTarget) {
            var targetId = parseInt(dropTarget.dataset.taskId);
            var targetParentId = dropTarget.dataset.parentId !== '' ? parseInt(dropTarget.dataset.parentId) : null;
            var targetOrder = parseInt(dropTarget.dataset.order);

            if (dropPosition === 'before') {
                moveTask(taskId, targetParentId, targetOrder);
            } else {
                moveTask(taskId, targetParentId, targetOrder + 1);
            }
        }
        document.querySelectorAll('.task-item').forEach(function(el) {
            el.classList.remove('drag-over');
        });
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

function moveTask(taskId, parentId, position) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    var pageId = parseInt(el.dataset.pageId);

    apiRequest('/tasks/' + taskId + '/move', 'PUT', {
        parentId: parentId, position: position
    }).then(function() {
        reloadPage(pageId);
    });
}

// ==========================================
// Context Menu
// ==========================================

function showContextMenu(x, y, task) {
    var menu = document.getElementById('context-menu');
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
    menu.style.display = 'block';

    var menuWidth = menu.offsetWidth;
    var menuHeight = menu.offsetHeight;
    var winWidth = window.innerWidth;
    var winHeight = window.innerHeight;
    if (x + menuWidth > winWidth) menu.style.left = (winWidth - menuWidth - 10) + 'px';
    if (y + menuHeight > winHeight) menu.style.top = (winHeight - menuHeight - 10) + 'px';
}

function handleContextAction(action) {
    var el = document.querySelector('.task-item[data-task-id="' + currentTaskId + '"]');
    if (!el) return;

    var pageId = parseInt(el.dataset.pageId);

    switch (action) {
        case 'add-child':
            apiRequest('/pages/' + pageId + '/tasks', 'POST', {
                text: '', parentId: currentTaskId, order: 0
            }).then(function() { reloadPage(pageId); });
            break;
        case 'add-above':
            apiRequest('/pages/' + pageId + '/tasks', 'POST', {
                text: '', parentId: null, order: 999
            }).then(function() { reloadPage(pageId); });
            break;
        case 'add-below':
            apiRequest('/pages/' + pageId + '/tasks', 'POST', {
                text: '', parentId: null, order: 999
            }).then(function() { reloadPage(pageId); });
            break;
        case 'edit':
            openTaskModal(currentTaskId);
            break;
        case 'set-status':
            toggleTaskStatusSimple(currentTaskId);
            break;
        case 'set-priority':
            togglePriority(currentTaskId);
            break;
        case 'delete':
            handleTaskDeleteOrFinish(currentTaskId, false, {
                pageId: pageId,
                onToggleStatus: function(taskId, completed) {
                    var newStatus = completed ? 'finished' : 'processed';
                    apiRequest('/tasks/' + taskId + '/status', 'PUT', { status: newStatus }).then(function() {
                        reloadPage(pageId);
                    });
                },
                onDelete: function(taskId) {
                    apiRequest('/tasks/' + taskId, 'DELETE').then(function() { reloadPage(pageId); });
                }
            });
            break;
    }
}

function handleTaskDeleteOrFinish(taskId, isCompleted, options) {
    if (isCompleted) {
        // Уже завершена — удаляем без подтверждения
        options.onDelete(taskId);
    } else {
        // Не завершена — устанавливаем статус finished
        options.onToggleStatus(taskId, true);
    }
}

function toggleTaskStatusSimple(taskId) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    var pageId = parseInt(el.dataset.pageId);
    var isCompleted = el.querySelector('.task-checkbox').checked;
    var nextStatus = isCompleted ? 'processed' : 'finished';
    apiRequest('/tasks/' + taskId + '/status', 'PUT', { status: nextStatus }).then(function() {
        reloadPage(pageId);
    });
}

function togglePriority(taskId) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    var pageId = parseInt(el.dataset.pageId);
    var isPriority = el.querySelector('.task-priority').classList.contains('high');
    apiRequest('/tasks/' + taskId, 'PUT', { isPriority: !isPriority }).then(function() {
        reloadPage(pageId);
    });
}

// ==========================================
// Task Modal
// ==========================================

function openTaskModal(taskId) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    if (!el) return;
    currentTaskId = taskId;

    var textEl = el.querySelector('.task-text');
    document.getElementById('modal-task-text').innerHTML = textEl ? textEl.innerHTML : '';

    var description = '';
    var isPriority = false;
    var currentStatus = 'processed';

    var checkbox = el.querySelector('.task-checkbox');
    if (checkbox && checkbox.checked) {
        currentStatus = 'finished';
    }

    var priorityEl = el.querySelector('.task-priority');
    if (priorityEl && priorityEl.classList.contains('high')) {
        isPriority = true;
    }

    var badge = el.querySelector('.task-status-badge');
    if (badge) {
        currentStatus = badge.textContent;
    }

    document.getElementById('modal-task-description').value = description;
    document.getElementById('modal-task-priority').value = isPriority ? '1' : '0';

    var statusSelect = document.getElementById('modal-task-status');
    statusSelect.innerHTML = '';
    statuses.forEach(function(s) {
        var opt = document.createElement('option');
        opt.value = s.systemName;
        opt.textContent = s.name;
        if (s.systemName === currentStatus) opt.selected = true;
        statusSelect.appendChild(opt);
    });

    document.getElementById('task-modal').style.display = 'block';
}

function saveTaskFromModal() {
    var text = document.getElementById('modal-task-text').innerHTML;
    var description = document.getElementById('modal-task-description').value;
    var status = document.getElementById('modal-task-status').value;
    var isPriority = document.getElementById('modal-task-priority').value === '1';
    var assignee = document.getElementById('modal-task-assignee').value;

    var el = document.querySelector('.task-item[data-task-id="' + currentTaskId + '"]');
    var pageId = parseInt(el.dataset.pageId);

    apiRequest('/tasks/' + currentTaskId, 'PUT', {
        text: text, description: description, status: status,
        isPriority: isPriority, assignee: assignee || null
    }).then(function() {
        reloadPage(pageId);
        closeModal();
    });
}

function closeModal() {
    document.getElementById('task-modal').style.display = 'none';
    currentTaskId = null;
}