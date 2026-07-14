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
    var rootTasks = document.querySelectorAll('.task-item[data-page-id="' + pageId + '"][data-parent-id=""]');
    var order = rootTasks.length;
    apiRequest('/pages/' + pageId + '/tasks', 'POST', {
        text: '', parentId: null, order: order
    }).then(function(response) {
        var task = response.task;
        // Try to find container — could be in page editor or in grid card
        var container = document.getElementById('tasks-container');
        if (!container) {
            var card = document.querySelector('.page-card[data-page-id="' + pageId + '"]');
            if (card) {
                container = card.querySelector('.page-card-editor');
            }
        }
        if (container) {
            var options = buildInlineOptions(pageId);
            var el = renderTaskItem(task, 0, [task], options);
            container.appendChild(el);
            focusTaskText(task.id);
        }
    });
}

// Default reloadTasks — finds the card in grid or updates tasks-container
window.reloadTasks = function(pageId, tasks) {
    var card = document.querySelector('.page-card[data-page-id="' + pageId + '"]');
    if (card) {
        var editorContainer = card.querySelector('.page-card-editor');
        if (editorContainer) {
            renderInlineTasks(tasks || [], editorContainer, pageId, 10);
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
    div.dataset.assignee = task.assignee || '';
    div.dataset.status = task.status || '';
    div.dataset.description = task.description || '';

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
                handleTaskDeleteOrFinish(task.id, isCompleted, options, true);
            }
        }
    });

    row.addEventListener('dblclick', function() {
        openTaskModal(task.id);
    });

    return div;
}

// ==========================================
// Focus helper — set cursor to task text
// ==========================================

function focusTaskText(taskId) {
    var textEl = document.querySelector('.task-item[data-task-id="' + taskId + '"] .task-text');
    if (textEl) {
        textEl.focus();
        // Place cursor at end of text
        var range = document.createRange();
        var sel = window.getSelection();
        range.selectNodeContents(textEl);
        range.collapse(false);
        sel.removeAllRanges();
        sel.addRange(range);
    }
}

// ==========================================
// Build inline options (reusable)
// ==========================================

function buildInlineOptions(pageId) {
    return {
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
            }).then(function(response) {
                var task = response.task;
                var parentEl = document.querySelector('.task-item[data-task-id="' + parentId + '"]');
                if (parentEl) {
                    var childrenContainer = parentEl.querySelector('.task-children');
                    if (!childrenContainer) {
                        childrenContainer = document.createElement('div');
                        childrenContainer.className = 'task-children';
                        parentEl.appendChild(childrenContainer);
                    }
                    var options = buildInlineOptions(pageId);
                    var el = renderTaskItem(task, parseInt(parentEl.dataset.level) + 1, [task], options);
                    childrenContainer.appendChild(el);
                    focusTaskText(task.id);
                }
            });
        },
        onDelete: function(taskId) {
            apiRequest('/tasks/' + taskId, 'DELETE').then(function() {
                var el = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
                if (el) {
                    el.remove();
                }
            });
        },
        onAddAfter: function(taskId) {
            var currentEl = document.querySelector('.task-item[data-task-id="' + taskId + '"]');
            var parentId = currentEl && currentEl.dataset.parentId ? parseInt(currentEl.dataset.parentId) : null;
            var parentIdAttr = parentId !== null ? parentId : '';
            var siblings = currentEl ? currentEl.parentNode.querySelectorAll('.task-item[data-parent-id="' + parentIdAttr + '"]') : [];
            var index = Array.prototype.indexOf.call(siblings, currentEl);
            apiRequest('/pages/' + pageId + '/tasks', 'POST', {
                text: '', parentId: parentId, position: index + 1
            }).then(function(response) {
                var task = response.task;
                if (currentEl) {
                    var container = currentEl.parentNode;
                    var level = parseInt(currentEl.dataset.level) || 0;
                    var options = buildInlineOptions(pageId);
                    var el = renderTaskItem(task, level, [task], options);
                    var nextEl = currentEl.nextElementSibling;
                    if (nextEl) {
                        container.insertBefore(el, nextEl);
                    } else {
                        container.appendChild(el);
                    }
                    focusTaskText(task.id);
                }
            });
        }
    };
}

// ==========================================
// Inline Tasks (for pages grid on main page)
// ==========================================

function renderInlineTasks(pageTasks, container, pageId, maxVisible) {
    var options = buildInlineOptions(pageId);

    container.innerHTML = '';

    if (pageTasks.length === 0) {
        container.innerHTML = '<div class="empty-tasks-bottom"><button class="btn empty-add-task-btn" onclick="addNewTask(' + pageId + ')"><i class="fas fa-plus"></i> Задача</button></div>';
        return;
    }

    var rootTasks = pageTasks.filter(function(t) { return !t.parentId; });
    rootTasks.forEach(function(task) {
        container.appendChild(renderTaskItem(task, 0, pageTasks, options));
    });

    // If maxVisible is set and there are more items, hide excess and add expand button
    if (maxVisible > 0) {
        var allItems = container.querySelectorAll('.task-item');
        if (allItems.length > maxVisible) {
            // Check if this container was already expanded by the user
            var isAlreadyExpanded = container.closest('[data-tasks-expanded="true"]') !== null;
            if (!isAlreadyExpanded) {
                for (var i = maxVisible; i < allItems.length; i++) {
                    allItems[i].classList.add('task-item-collapsed');
                }

                var hiddenCount = allItems.length - maxVisible;
                var expandBtn = document.createElement('button');
                expandBtn.className = 'btn task-expand-btn';
                expandBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Раскрыть все (' + hiddenCount + ')';
                expandBtn.addEventListener('click', function() {
                    var hidden = container.querySelectorAll('.task-item-collapsed');
                    for (var j = 0; j < hidden.length; j++) {
                        hidden[j].classList.remove('task-item-collapsed');
                        hidden[j].classList.add('task-item-expanded');
                    }
                    expandBtn.remove();
                    // Mark the card as expanded so it won't show the button again on re-render
                    var card = container.closest('.page-card');
                    if (card) {
                        card.dataset.tasksExpanded = 'true';
                    }
                });
                container.appendChild(expandBtn);
            }
        }
    }

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

    // Задача не может стать родителем самой себя
    if (parentId === taskId) {
        return;
    }

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
    hideAllContextMenus();
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
    hideAllContextMenus();
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

    // Сдвинуть вправо (indent) — если есть предыдущий sibling, к которому можно стать дочерним
    var el = document.querySelector('.task-item[data-task-id="' + task.id + '"]');
    var hasPrevSibling = false;
    if (el) {
        var parentId = el.dataset.parentId !== '' ? parseInt(el.dataset.parentId) : null;
        var order = parseInt(el.dataset.order);
        var siblings = document.querySelectorAll('.task-item[data-parent-id="' + (parentId !== null ? parentId : '') + '"]');
        siblings.forEach(function(s) {
            var sOrder = parseInt(s.dataset.order);
            if (sOrder < order) {
                hasPrevSibling = true;
            }
        });
    }
    if (hasPrevSibling) {
        var indentLi = document.createElement('li');
        indentLi.dataset.action = 'indent';
        indentLi.innerHTML = '<i class="fas fa-indent"></i> Сдвинуть вправо';
        indentLi.addEventListener('click', function(e) {
            e.stopPropagation();
            indentTask(currentTaskId);
            hideAllContextMenus();
        });
        list.appendChild(indentLi);
    }

    // Сдвинуть влево (outdent) — если есть родитель
    var hasParent = el && el.dataset.parentId !== '';
    if (hasParent) {
        var outdentLi = document.createElement('li');
        outdentLi.dataset.action = 'outdent';
        outdentLi.innerHTML = '<i class="fas fa-dedent"></i> Сдвинуть влево';
        outdentLi.addEventListener('click', function(e) {
            e.stopPropagation();
            outdentTask(currentTaskId);
            hideAllContextMenus();
        });
        list.appendChild(outdentLi);
    }

    // Divider — только если был добавлен хотя бы один из пунктов indent/outdent
    if (hasPrevSibling || hasParent) {
        var dividerIndent = document.createElement('li');
        dividerIndent.className = 'divider';
        list.appendChild(dividerIndent);
    }

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
    hideAllContextMenus();
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
        li.innerHTML = '<i class="fas fa-user"></i> ' + (u.displayName);
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

function handleTaskDeleteOrFinish(taskId, isCompleted, options, forceDelete) {
    if (forceDelete || isCompleted) {
        // Force delete (Backspace on empty) or already completed — just delete
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

var newStatusIconPicker = null;

function showNewStatusModal() {
    var modal = document.getElementById('new-status-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('new-status-name-input').value = '';

        // Initialize icon picker if not yet created
        var pickerContainer = document.getElementById('new-status-icon-picker');
        if (pickerContainer && !pickerContainer.querySelector('.icon-picker-wrapper')) {
            newStatusIconPicker = createIconPicker('fa-circle', function() {});
            pickerContainer.appendChild(newStatusIconPicker);
        }

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

    var icon = newStatusIconPicker ? newStatusIconPicker.getIcon() : 'fa-circle';

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

// ==========================================
// Page Settings Menu (Burger Menu)
// ==========================================

function showPageSettingsMenu(x, y, pageId, pageTitle) {
    hideAllContextMenus();
    var menu = document.getElementById('context-menu-page');
    if (!menu) return;

    var input = document.getElementById('page-settings-title-input');
    input.value = pageTitle || '';
    input.dataset.pageId = pageId;

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

    setTimeout(function() {
        input.focus();
        input.select();
    }, 100);
}

function savePageTitle() {
    var input = document.getElementById('page-settings-title-input');
    var title = input.value.trim();
    var pageId = parseInt(input.dataset.pageId);
    if (!title || !pageId) {
        input.focus();
        return;
    }

    apiRequest('/pages/' + pageId, 'PUT', { title: title }).then(function() {
        hideAllContextMenus();
        // Update the card title on the main page
        var card = document.querySelector('.page-card[data-page-id="' + pageId + '"]');
        if (card) {
            var titleLink = card.querySelector('.page-card-title');
            if (titleLink) {
                titleLink.textContent = title;
            }
            var burgerBtn = card.querySelector('.burger-btn[data-page-id="' + pageId + '"]');
            if (burgerBtn) {
                burgerBtn.dataset.pageTitle = title;
            }
        }
        // If on the page editor, update the title
        var pageTitleEl = document.getElementById('full-page-title');
        if (pageTitleEl) {
            pageTitleEl.textContent = title;
        }
    }).catch(function(err) {
        console.error('Failed to save page title:', err);
    });
}

function deletePage() {
    var input = document.getElementById('page-settings-title-input');
    var pageId = parseInt(input.dataset.pageId);
    if (!pageId) return;

    if (!confirm('Вы уверены, что хотите удалить эту страницу?')) return;

    apiRequest('/pages/' + pageId, 'DELETE').then(function() {
        hideAllContextMenus();
        // If on the page editor, redirect to main
        if (window.location.pathname.startsWith('/page/')) {
            window.location.href = '/';
        } else {
            // On main page — remove the card
            var card = document.querySelector('.page-card[data-page-id="' + pageId + '"]');
            if (card) {
                card.remove();
            }
        }
    }).catch(function(err) {
        console.error('Failed to delete page:', err);
        alert('Не удалось удалить страницу. Возможно, у вас нет доступа.');
    });
}

// ==========================================
// User Name Edit Menu
// ==========================================

function showUserNameMenu(x, y, currentName) {
    hideAllContextMenus();
    var menu = document.getElementById('context-menu-username');
    if (!menu) return;

    var input = document.getElementById('username-edit-input');
    input.value = currentName || '';

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

    setTimeout(function() {
        input.focus();
        input.select();
    }, 100);
}

function saveUserName() {
    var input = document.getElementById('username-edit-input');
    var name = input.value.trim();
    if (!name) {
        input.focus();
        return;
    }

    apiRequest('/me/name', 'PUT', { name: name }).then(function() {
        hideAllContextMenus();
        location.reload();
    }).catch(function(err) {
        console.error('Failed to save user name:', err);
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

    var isPriority = false;
    var currentStatus = el.dataset.status || 'processed';

    var row = el.querySelector('.task-row');
    if (row && row.classList.contains('priority-high')) {
        isPriority = true;
    }

    // Read description from data attribute
    var description = el.dataset.description || '';
    document.getElementById('modal-task-description').value = description;
    document.getElementById('modal-task-priority').checked = isPriority;

    // Fill hidden native select (for value)
    var statusSelect = document.getElementById('modal-task-status');
    statusSelect.innerHTML = '';

    // Fill custom status dropdown with FontAwesome icons
    var dropdown = document.getElementById('modal-status-dropdown');
    dropdown.innerHTML = '';
    var triggerText = document.getElementById('modal-status-trigger').querySelector('.custom-select-trigger-text');

    statuses.forEach(function(s) {
        // Native option
        var opt = document.createElement('option');
        opt.value = s.systemName;
        if (s.systemName === currentStatus) opt.selected = true;
        statusSelect.appendChild(opt);

        // Custom dropdown item
        var item = document.createElement('div');
        item.className = 'custom-select-item';
        item.dataset.value = s.systemName;

        var iconHtml = '';
        if (s.systemName === 'processed') {
            iconHtml = '<span class="menu-checkbox-icon empty"></span>';
        } else if (s.systemName === 'finished') {
            iconHtml = '<span class="menu-checkbox-icon checked"><i class="fas fa-check"></i></span>';
        } else {
            iconHtml = '<i class="fas ' + (s.icon || 'fa-circle') + '"></i>';
        }
        item.innerHTML = iconHtml + ' ' + s.name;

        if (s.systemName === currentStatus) {
            item.classList.add('selected');
            triggerText.innerHTML = iconHtml + ' ' + s.name;
        }

        item.addEventListener('click', function(e) {
            e.stopPropagation();
            // Update hidden select
            statusSelect.value = s.systemName;
            // Update trigger text
            triggerText.innerHTML = iconHtml + ' ' + s.name;
            // Update selected state
            dropdown.querySelectorAll('.custom-select-item').forEach(function(el) {
                el.classList.remove('selected');
            });
            item.classList.add('selected');
            // Close dropdown
            dropdown.classList.remove('open');
        });

        dropdown.appendChild(item);
    });

    // Fill assignee select
    var assigneeSelect = document.getElementById('modal-task-assignee');
    assigneeSelect.innerHTML = '<option value="">Без исполнителя</option>';
    var currentAssignee = el.dataset.assignee || '';
    projectUsers.forEach(function(u) {
        var opt = document.createElement('option');
        opt.value = u.email;
        opt.textContent = u.displayName;
        if (currentAssignee === u.email) {
            opt.selected = true;
        }
        assigneeSelect.appendChild(opt);
    });

    document.getElementById('task-modal').style.display = 'flex';
}

function saveTaskFromModal() {
    var text = document.getElementById('modal-task-text').innerHTML;
    var description = document.getElementById('modal-task-description').value;
    var status = document.getElementById('modal-task-status').value;
    var isPriority = document.getElementById('modal-task-priority').checked;
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

// ==========================================
// Project Settings Modal
// ==========================================

function openProjectSettingsModal() {
    if (!currentProjectId) return;
    document.getElementById('project-settings-modal').style.display = 'flex';
    loadProjectSettingsData();

    // Initialize icon picker for the "add status" form if not yet created
    var pickerContainer = document.getElementById('settings-status-icon-picker');
    if (pickerContainer && !pickerContainer.querySelector('.icon-picker-wrapper')) {
        addStatusIconPicker = createIconPicker('fa-circle', function(iconClass) {
            // Just update selection, no API call needed
        });
        pickerContainer.appendChild(addStatusIconPicker);
    }
}

function closeProjectSettingsModal() {
    document.getElementById('project-settings-modal').style.display = 'none';
}

function loadProjectSettingsData() {
    loadProjectUsersList();
    loadProjectSettingsStatuses();
    loadProjectSettingsName();
}

function loadProjectSettingsName() {
    var selector = document.getElementById('project-selector');
    var selectedOption = selector.options[selector.selectedIndex];
    var currentName = selectedOption ? selectedOption.textContent : '';
    document.getElementById('rename-project-input').value = currentName;
}

function loadProjectUsersList() {
    var list = document.getElementById('project-users-list');
    list.innerHTML = '<li class="user-list-empty">Загрузка...</li>';

    apiRequest('/projects/' + currentProjectId + '/users').then(function(response) {
        list.innerHTML = '';
        var users = response.users || [];
        if (users.length === 0) {
            list.innerHTML = '<li class="user-list-empty">Нет участников</li>';
            return;
        }
        users.forEach(function(u) {
            var li = document.createElement('li');
            li.innerHTML = '<span>' + u.displayName + ' <span class="user-email">(' + u.email + ')</span></span>';
            list.appendChild(li);
        });
    }).catch(function() {
        list.innerHTML = '<li class="user-list-empty">Ошибка загрузки</li>';
    });
}

function loadProjectSettingsStatuses() {
    var list = document.getElementById('settings-statuses-list');
    list.innerHTML = '<li class="status-list-empty">Загрузка...</li>';

    apiRequest('/projects/' + currentProjectId + '/statuses').then(function(response) {
        list.innerHTML = '';
        var statuses = response.statuses || [];
        if (statuses.length === 0) {
            list.innerHTML = '<li class="status-list-empty">Нет статусов</li>';
            return;
        }
        statuses.forEach(function(s) {
            var li = document.createElement('li');
            li.dataset.statusId = s.id;

            var isDefault = (s.systemName === 'processed' || s.systemName === 'finished');

            var info = document.createElement('div');
            info.className = 'status-info';

            if (!isDefault) {
                // Icon picker with search
                var iconPicker = createIconPicker(s.icon || 'fa-circle', function(iconClass) {
                    apiRequest('/statuses/' + s.id, 'PUT', { icon: iconClass }).then(function() {
                        if (currentProjectId) {
                            loadProjectStatuses(currentProjectId);
                        }
                    });
                });
                info.appendChild(iconPicker);
            }

            var nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.className = 'status-name-input';
            nameInput.value = s.name;
            nameInput.dataset.statusId = s.id;
            nameInput.dataset.originalName = s.name;
            info.appendChild(nameInput);

            li.appendChild(info);

            var actions = document.createElement('div');
            actions.className = 'status-actions';

            var saveBtn = document.createElement('button');
            saveBtn.className = 'status-save-btn';
            saveBtn.title = 'Сохранить';
            saveBtn.innerHTML = '<i class="fas fa-check"></i>';
            saveBtn.dataset.statusId = s.id;
            saveBtn.style.display = 'none';
            saveBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                saveStatusName(s.id, nameInput.value);
            });
            actions.appendChild(saveBtn);

            if (!isDefault) {
                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'status-delete-btn';
                deleteBtn.title = 'Удалить статус';
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                deleteBtn.dataset.statusId = s.id;
                deleteBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    deleteStatus(s.id);
                });
                actions.appendChild(deleteBtn);
            }

            li.appendChild(actions);
            list.appendChild(li);

            // Show save button on input change
            nameInput.addEventListener('input', function() {
                var hasChanged = nameInput.value !== nameInput.dataset.originalName;
                saveBtn.style.display = hasChanged ? 'inline-flex' : 'none';
            });

            nameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveStatusName(s.id, nameInput.value);
                }
            });
        });
    }).catch(function() {
        list.innerHTML = '<li class="status-list-empty">Ошибка загрузки</li>';
    });
}

function saveStatusName(statusId, newName) {
    if (!newName.trim()) return;

    apiRequest('/statuses/' + statusId, 'PUT', { name: newName.trim() }).then(function(response) {
        // Update the input's original name
        var input = document.querySelector('.status-name-input[data-status-id="' + statusId + '"]');
        if (input) {
            input.dataset.originalName = newName.trim();
            var saveBtn = document.querySelector('.status-save-btn[data-status-id="' + statusId + '"]');
            if (saveBtn) saveBtn.style.display = 'none';
        }
        // Reload statuses in the main UI
        if (currentProjectId) {
            loadProjectStatuses(currentProjectId);
        }
    }).catch(function(err) {
        console.error('Failed to save status name:', err);
    });
}

function deleteStatus(statusId) {
    if (!confirm('Вы уверены, что хотите удалить этот статус? Задачи с этим статусом будут сброшены.')) return;

    apiRequest('/statuses/' + statusId, 'DELETE').then(function() {
        loadProjectSettingsStatuses();
        if (currentProjectId) {
            loadProjectStatuses(currentProjectId);
        }
    }).catch(function(err) {
        console.error('Failed to delete status:', err);
        alert('Не удалось удалить статус.');
    });
}

function inviteUser() {
    var emailInput = document.getElementById('invite-user-email');
    var email = emailInput.value.trim();
    var messageEl = document.getElementById('invite-user-message');

    if (!email) {
        messageEl.className = 'form-message error';
        messageEl.textContent = 'Введите email';
        return;
    }

    messageEl.className = 'form-message';
    messageEl.textContent = 'Отправка...';

    apiRequest('/projects/' + currentProjectId + '/invite', 'POST', { email: email }).then(function(response) {
        messageEl.className = 'form-message success';
        messageEl.textContent = 'Пользователь приглашён!';
        emailInput.value = '';
        loadProjectUsersList();
    }).catch(function(err) {
        messageEl.className = 'form-message error';
        if (err.data && err.data.error) {
            messageEl.textContent = err.data.error;
        } else {
            messageEl.textContent = 'Ошибка при отправке приглашения';
        }
    });
}

function renameProject() {
    var input = document.getElementById('rename-project-input');
    var name = input.value.trim();
    var messageEl = document.getElementById('rename-project-message');

    if (!name) {
        messageEl.className = 'form-message error';
        messageEl.textContent = 'Введите название';
        return;
    }

    messageEl.className = 'form-message';
    messageEl.textContent = 'Сохранение...';

    apiRequest('/projects/' + currentProjectId + '/rename', 'PUT', { name: name }).then(function(response) {
        messageEl.className = 'form-message success';
        messageEl.textContent = 'Название сохранено!';
        // Update the selector
        var selector = document.getElementById('project-selector');
        var selectedOption = selector.options[selector.selectedIndex];
        if (selectedOption) {
            selectedOption.textContent = name;
        }
    }).catch(function(err) {
        messageEl.className = 'form-message error';
        if (err.data && err.data.error) {
            messageEl.textContent = err.data.error;
        } else {
            messageEl.textContent = 'Ошибка при сохранении';
        }
    });
}

var addStatusIconPicker = null;

function addStatusFromSettings() {
    var nameInput = document.getElementById('settings-status-name-input');
    var name = nameInput.value.trim();
    var messageEl = document.getElementById('add-status-message');

    if (!name) {
        messageEl.className = 'form-message error';
        messageEl.textContent = 'Введите название статуса';
        return;
    }

    var icon = addStatusIconPicker ? addStatusIconPicker.getIcon() : 'fa-circle';

    messageEl.className = 'form-message';
    messageEl.textContent = 'Добавление...';

    apiRequest('/statuses', 'POST', {
        name: name,
        systemName: name,
        icon: icon,
        projectId: currentProjectId
    }).then(function() {
        messageEl.className = 'form-message success';
        messageEl.textContent = 'Статус добавлен!';
        nameInput.value = '';
        // Reset icon picker
        if (addStatusIconPicker) {
            addStatusIconPicker.setIcon('fa-circle');
        }
        loadProjectSettingsStatuses();
        if (currentProjectId) {
            loadProjectStatuses(currentProjectId);
        }
    }).catch(function(err) {
        messageEl.className = 'form-message error';
        if (err.data && err.data.error) {
            messageEl.textContent = err.data.error;
        } else {
            messageEl.textContent = 'Ошибка при добавлении статуса';
        }
    });
}

// ==========================================
// Icon Picker Component
// ==========================================

var fontawesomeIconList = null;

function getFontawesomeIcons() {
    if (fontawesomeIconList) return fontawesomeIconList;

    fontawesomeIconList = [];
    var seen = {};

    for (var i = 0; i < document.styleSheets.length; i++) {
        try {
            var sheet = document.styleSheets[i];
            if (!sheet.cssRules) continue;
            for (var j = 0; j < sheet.cssRules.length; j++) {
                var rule = sheet.cssRules[j];
                if (rule.selectorText && rule.selectorText.endsWith('::before')) {
                    // Extract the icon name: find last .fa-XXXX class in selector
                    var match = rule.selectorText.match(/\.(fa-[a-zA-Z0-9\-]+)/);
                    if (match) {
                        var name = match[1];
                        if (!seen[name]) {
                            seen[name] = true;
                            fontawesomeIconList.push(name);
                        }
                    }
                }
            }
        } catch (e) {
            // Some stylesheets are not accessible due to CORS
        }
    }

    return fontawesomeIconList;
}

function createIconPicker(selectedIcon, onChange) {
    var wrapper = document.createElement('div');
    wrapper.className = 'icon-picker-wrapper';

    var trigger = document.createElement('div');
    trigger.className = 'icon-picker-trigger';
    trigger.innerHTML = '<i class="fas ' + selectedIcon + '"></i> <i class="fas fa-chevron-down"></i>';

    var dropdown = document.createElement('div');
    dropdown.className = 'icon-picker-dropdown';

    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'icon-picker-search';
    searchInput.placeholder = 'Поиск иконки...';

    var preview = document.createElement('div');
    preview.className = 'icon-picker-preview';
    preview.innerHTML = '<i class="fas ' + selectedIcon + '"></i>';

    var list = document.createElement('div');
    list.className = 'icon-picker-list';

    function renderIcons(query) {
        list.innerHTML = '';
        var allIcons = getFontawesomeIcons();
        var filtered = allIcons;
        if (query) {
            var q = query.toLowerCase().replace(/^fa-/, '');
            filtered = allIcons.filter(function(icon) {
                return icon.toLowerCase().indexOf(q) !== -1;
            });
        }
        filtered.forEach(function(iconClass) {
            var item = document.createElement('div');
            item.className = 'icon-picker-item' + (iconClass === selectedIcon ? ' selected' : '');
            item.innerHTML = '<i class="fas ' + iconClass + '"></i>';
            item.title = iconClass;
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                selectedIcon = iconClass;
                trigger.innerHTML = '<i class="fas ' + iconClass + '"></i> <i class="fas fa-chevron-down"></i>';
                preview.innerHTML = '<i class="fas ' + iconClass + '"></i>';
                dropdown.classList.remove('open');
                if (onChange) onChange(iconClass);
            });
            list.appendChild(item);
        });
    }

    searchInput.addEventListener('input', function() {
        renderIcons(this.value);
    });

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            searchInput.value = '';
            renderIcons('');
            setTimeout(function() { searchInput.focus(); }, 50);
        }
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    dropdown.appendChild(searchInput);
    dropdown.appendChild(preview);
    dropdown.appendChild(list);
    wrapper.appendChild(trigger);
    wrapper.appendChild(dropdown);

    // Public methods
    wrapper.getIcon = function() { return selectedIcon; };
    wrapper.setIcon = function(icon) {
        selectedIcon = icon;
        trigger.innerHTML = '<i class="fas ' + icon + '"></i> <i class="fas fa-chevron-down"></i>';
        preview.innerHTML = '<i class="fas ' + icon + '"></i>';
    };

    return wrapper;
}