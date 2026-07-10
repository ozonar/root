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
let currentProjectId = null;
let statuses = [];
let projectUsers = [];
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
// Load project users
// ==========================================

function loadProjectStatuses(projectId) {
    if (!projectId) return Promise.resolve([]);
    return apiRequest('/projects/' + projectId + '/statuses').then(function(response) {
        statuses = response.statuses || [];
        return statuses;
    }).catch(function() {
        statuses = [];
        return [];
    });
}

function loadProjectUsers(projectId) {
    if (!projectId) return Promise.resolve([]);
    return apiRequest('/projects/' + projectId + '/users').then(function(response) {
        projectUsers = response.users || [];
        return projectUsers;
    }).catch(function() {
        projectUsers = [];
        return [];
    });
}

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

    // Checkbox with status icon
    var checkbox = document.createElement('span');
    checkbox.className = 'task-checkbox';
    if (task.status === 'finished') {
        checkbox.classList.add('checked');
        checkbox.innerHTML = '<i class="fas fa-check"></i>';
    } else if (task.status && task.status !== 'processed') {
        checkbox.classList.add('custom-status');
        var statusIcon = task.statusIcon || 'fa-circle';
        checkbox.innerHTML = '<i class="fas ' + statusIcon + '"></i>';
    } else {
        // processed or no status — empty square
        checkbox.classList.add('empty');
    }
    // ЛКМ — переключение статуса processed/finished
    checkbox.addEventListener('click', function(e) {
        e.stopPropagation();
        if (checkbox.classList.contains('checked')) {
            checkbox.className = 'task-checkbox empty';
            options.onToggleStatus(task.id, false);
        } else {
            checkbox.className = 'task-checkbox checked';
            checkbox.innerHTML = '<i class="fas fa-check"></i>';
            options.onToggleStatus(task.id, true);
        }
    });
    // ПКМ на чекбоксе — меню статусов
    checkbox.addEventListener('contextmenu', function(e) {
        e.stopPropagation();
        e.preventDefault();
        currentTaskId = task.id;
        showStatusContextMenu(e.pageX, e.pageY, task);
    });
    row.appendChild(checkbox);
// Priority — add class to row instead of icon
if (isHighPriority) {
    row.classList.add('priority-high');
}


    // Text
    var text = document.createElement('div');
    text.className = 'task-text' + (isCompleted ? ' completed' : '');
    text.contentEditable = 'true';
    text.innerHTML = task.text || '';
    // ПКМ на тексте — меню действий
    text.addEventListener('contextmenu', function(e) {
        e.stopPropagation();
        e.preventDefault();
        currentTaskId = task.id;
        showTaskContextMenu(e.pageX, e.pageY, task);
    });
    row.appendChild(text);

    // Status badge
    if (task.status && task.status !== 'processed') {
        var badge = document.createElement('span');
        badge.className = 'task-status-badge show';
        badge.textContent = task.statusName || task.status;
        row.appendChild(badge);
    }

    // Assignee
    var assigneeSpan = document.createElement('span');
    assigneeSpan.className = 'task-assignee';
    var assigneeName = task.assigneeName || task.assignee || '';
    if (assigneeName) {
        assigneeSpan.textContent = assigneeName;
    } else {
        assigneeSpan.innerHTML = '<span class="task-assignee-empty"><i class="fas fa-user"></i></span>';
    }
    assigneeSpan.title = 'Сменить исполнителя';
    // ЛКМ по исполнителю — меню выбора пользователя
    assigneeSpan.addEventListener('click', function(e) {
        e.stopPropagation();
        currentTaskId = task.id;
        showAssigneeMenu(e.pageX, e.pageY, task);
    });
    row.appendChild(assigneeSpan);

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
// Context Menu: Statuses (on checkbox right-click)
// ==========================================

function showStatusContextMenu(x, y, task) {
    var menu = document.getElementById('context-menu-status');
    if (!menu) return;

    // Build status list
    var list = menu.querySelector('.context-menu-list');
    list.innerHTML = '';

    statuses.forEach(function(s) {
        var li = document.createElement('li');
        li.dataset.status = s.systemName;
        if (s.systemName === 'processed') {
            li.innerHTML = '<span class="menu-checkbox-icon empty"></span> ' + s.name;
        } else if (s.systemName === 'finished') {
            li.innerHTML = '<span class="menu-checkbox-icon checked"><i class="fas fa-check"></i></span> ' + s.name;
        } else {
            li.innerHTML = '<i class="fas ' + (s.icon || 'fa-circle') + '"></i> ' + s.name;
        }
        if (task.status === s.systemName) {
            li.classList.add('selected');
        }
        li.addEventListener('click', function(e) {
            e.stopPropagation();
            setTaskStatus(currentTaskId, s.systemName);
            hideAllContextMenus();
        });
        list.appendChild(li);
    });

    // Divider
    var divider = document.createElement('li');
    divider.className = 'divider';
    list.appendChild(divider);

    // Add new status button
    var addLi = document.createElement('li');
    addLi.className = 'add-status-btn';
    addLi.innerHTML = '<i class="fas fa-plus"></i> Добавить новый статус';
    addLi.addEventListener('click', function(e) {
        e.stopPropagation();
        hideAllContextMenus();
        showNewStatusModal();
    });
    list.appendChild(addLi);

    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
    menu.style.display = 'block';

    // Adjust position
    var menuWidth = menu.offsetWidth;
    var menuHeight = menu.offsetHeight;
    var winWidth = window.innerWidth;
    var winHeight = window.innerHeight;
    if (x + menuWidth > winWidth) menu.style.left = (winWidth - menuWidth - 10) + 'px';
    if (y + menuHeight > winHeight) menu.style.top = (winHeight - menuHeight - 10) + 'px';
}

// ==========================================
// Context Menu: Task actions (on text right-click)
// ==========================================

function showTaskContextMenu(x, y, task) {
    var menu = document.getElementById('context-menu-task');
    if (!menu) return;

    var list = menu.querySelector('.context-menu-list');
    list.innerHTML = '';

    // Priority
    var priorityLi = document.createElement('li');
    priorityLi.dataset.action = 'set-priority';
    priorityLi.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Приоритет';
    if (task.isPriority) {
        priorityLi.classList.add('selected');
    }
    priorityLi.addEventListener('click', function(e) {
        e.stopPropagation();
        togglePriority(currentTaskId);
        hideAllContextMenus();
    });
    list.appendChild(priorityLi);

    // Divider
    var divider1 = document.createElement('li');
    divider1.className = 'divider';
    list.appendChild(divider1);

    // Edit
    var editLi = document.createElement('li');
    editLi.dataset.action = 'edit';
    editLi.innerHTML = '<i class="fas fa-edit"></i> Редактировать';
    editLi.addEventListener('click', function(e) {
        e.stopPropagation();
        openTaskModal(currentTaskId);
        hideAllContextMenus();
    });
    list.appendChild(editLi);

    // Divider
    var divider2 = document.createElement('li');
    divider2.className = 'divider';
    list.appendChild(divider2);

    // Delete
    var deleteLi = document.createElement('li');
    deleteLi.dataset.action = 'delete';
    deleteLi.className = 'danger';
    deleteLi.innerHTML = '<i class="fas fa-trash"></i> Удалить';
    deleteLi.addEventListener('click', function(e) {
        e.stopPropagation();
        var el = document.querySelector('.task-item[data-task-id="' + currentTaskId + '"]');
        var pageId = parseInt(el.dataset.pageId);
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
        hideAllContextMenus();
    });
    list.appendChild(deleteLi);

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

// ==========================================
// Assignee Menu (click on assignee name)
// ==========================================

function showAssigneeMenu(x, y, task) {
    var menu = document.getElementById('context-menu-assignee');
    if (!menu) return;

    var list = menu.querySelector('.context-menu-list');
    list.innerHTML = '';

    // "No assignee" option
    var noneLi = document.createElement('li');
    noneLi.innerHTML = '<i class="fas fa-user-slash"></i> Без исполнителя';
    if (!task.assignee) {
        noneLi.classList.add('selected');
    }
    noneLi.addEventListener('click', function(e) {
        e.stopPropagation();
        setTaskAssignee(currentTaskId, null);
        hideAllContextMenus();
    });
    list.appendChild(noneLi);

    // Divider
    var divider = document.createElement('li');
    divider.className = 'divider';
    list.appendChild(divider);

    // Project users
    projectUsers.forEach(function(u) {
        var li = document.createElement('li');
        li.innerHTML = '<i class="fas fa-user"></i> ' + (u.name || u.email);
        if (task.assignee === u.email) {
            li.classList.add('selected');
        }
        li.addEventListener('click', function(e) {
            e.stopPropagation();
            setTaskAssignee(currentTaskId, u.email);
            hideAllContextMenus();
        });
        list.appendChild(li);
    });

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

// ==========================================
// Context Menu Helpers
// ==========================================

function handleTaskDeleteOrFinish(taskId, isCompleted, options) {
    if (isCompleted) {
        // If already completed, just delete
        if (options.onDelete) {
            options.onDelete(taskId);
        }
    } else {
        // Mark as finished first
        if (options.onToggleStatus) {
            options.onToggleStatus(taskId, true);
        }
    }
}

function hideAllContextMenus() {
    var menus = document.querySelectorAll('.context-menu');
    menus.forEach(function(m) { m.style.display = 'none'; });
}

function setTaskStatus(taskId, statusSystemName) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    var pageId = parseInt(el.dataset.pageId);
    apiRequest('/tasks/' + taskId + '/status', 'PUT', { status: statusSystemName }).then(function() {
        reloadPage(pageId);
    });
}

function setTaskAssignee(taskId, email) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    var pageId = parseInt(el.dataset.pageId);
    apiRequest('/tasks/' + taskId, 'PUT', { assignee: email }).then(function() {
        reloadPage(pageId);
    });
}

// ==========================================
// New Status Modal
// ==========================================

function showNewStatusModal() {
    var modal = document.getElementById('new-status-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('new-status-name-input').value = '';
        setTimeout(function() {
            document.getElementById('new-status-name-input').focus();
        }, 100);
    }
}

function closeNewStatusModal() {
    document.getElementById('new-status-modal').style.display = 'none';
}

function confirmNewStatus() {
    var name = document.getElementById('new-status-name-input').value.trim();
    if (!name) return;

    var iconInput = document.getElementById('new-status-icon-input');
    var selectedIcon = iconInput.querySelector('.icon-option.selected');
    var icon = selectedIcon ? selectedIcon.dataset.value : 'fa-circle';

    closeNewStatusModal();

    apiRequest('/statuses', 'POST', {
        name: name,
        systemName: name,
        icon: icon,
        projectId: currentProjectId
    }).then(function() {
        // Reload current page to get updated statuses
        var el = document.querySelector('.task-item[data-task-id="' + currentTaskId + '"]');
        if (el) {
            var pageId = parseInt(el.dataset.pageId);
            reloadPage(pageId);
        }
    });
}

// Initialize icon select behavior
document.addEventListener('click', function(e) {
    var iconOption = e.target.closest('.icon-option');
    if (iconOption) {
        var container = iconOption.closest('.icon-select');
        container.querySelectorAll('.icon-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
        iconOption.classList.add('selected');
    }
});

function togglePriority(taskId) {
    var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
    var pageId = parseInt(el.dataset.pageId);
    var row = el.querySelector('.task-row');
    var isPriority = row.classList.contains('priority-high');
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
    if (checkbox && checkbox.classList.contains('checked')) {
        currentStatus = 'finished';
    }

    var row = el.querySelector('.task-row');
    if (row && row.classList.contains('priority-high')) {
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