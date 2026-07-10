<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* index.html.twig */
class __TwigTemplate_96170077b1230f7a150498769cd9f0e7 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'title' => [$this, 'block_title'],
            'body' => [$this, 'block_body'],
            'javascripts' => [$this, 'block_javascripts'],
        ];
    }

    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return "base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "index.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "index.html.twig"));

        $this->parent = $this->load("base.html.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

    }

    // line 3
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_title(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "title"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "title"));

        yield "Checker";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_body(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "body"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "body"));

        // line 6
        yield "<div id=\"app\">
    <!-- Header -->
    <header class=\"header\">
        <div class=\"header-left\">
            <a href=\"/\" class=\"header-title\">Checker</a>
        </div>
        <div class=\"header-center\">
            <select id=\"project-selector\" class=\"project-selector\">
            </select>
        </div>
        <div class=\"header-right\">
            <span id=\"user-name\" class=\"user-name\"></span>
            <button id=\"logout-btn\" class=\"btn btn-sm btn-outline\" title=\"Выйти\">
                <i class=\"fas fa-sign-out-alt\"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class=\"main-content\">
        <div class=\"pages-grid\" id=\"pages-grid\">
            <div class=\"loading-pages\">Загрузка...</div>
        </div>
        <div class=\"new-page-bottom\">
            <button id=\"new-page-btn\" class=\"btn btn-primary\" title=\"Новая страница\">
                <i class=\"fas fa-plus\"></i> Страница
            </button>
        </div>
    </main>
</div>

<!-- Full Page Editor (hidden by default) -->
<div id=\"full-editor\" class=\"full-editor\" style=\"display: none;\">
    <div class=\"full-editor-header\">
        <button id=\"back-to-main\" class=\"btn btn-sm btn-outline\">
            <i class=\"fas fa-arrow-left\"></i> Назад
        </button>
        <h2 id=\"full-page-title\" contenteditable=\"true\" class=\"page-title-editable\"></h2>
        <div class=\"editor-actions\">
            <button id=\"add-task-btn\" class=\"btn btn-sm btn-primary\" title=\"Добавить задачу\">
                <i class=\"fas fa-plus\"></i> Задача
            </button>
        </div>
    </div>
    <div class=\"full-editor-body\">
        <div class=\"tasks-container\" id=\"tasks-container\"></div>
    </div>
</div>

<!-- Task Modal -->
<div class=\"modal-overlay\" id=\"task-modal\" style=\"display: none;\">
    <div class=\"modal\">
        <div class=\"modal-header\">
            <h3 id=\"modal-title\">Редактирование задачи</h3>
            <button class=\"modal-close\" id=\"modal-close\">&times;</button>
        </div>
        <div class=\"modal-body\">
            <div class=\"form-group\">
                <label>Текст задачи</label>
                <div id=\"modal-task-text\" contenteditable=\"true\" class=\"modal-editable\"></div>
            </div>
            <div class=\"form-group\">
                <label>Описание</label>
                <textarea id=\"modal-task-description\" class=\"modal-textarea\" rows=\"4\"></textarea>
            </div>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>Статус</label>
                    <select id=\"modal-task-status\" class=\"form-select\"></select>
                </div>
                <div class=\"form-group\">
                    <label>Приоритет</label>
                    <select id=\"modal-task-priority\" class=\"form-select\">
                        <option value=\"0\">Обычный</option>
                        <option value=\"1\">Высокий</option>
                    </select>
                </div>
            </div>
            <div class=\"form-group\">
                <label>Ответственный</label>
                <input type=\"text\" id=\"modal-task-assignee\" class=\"form-input\" placeholder=\"Email ответственного\">
            </div>
        </div>
        <div class=\"modal-footer\">
            <button class=\"btn btn-secondary\" id=\"modal-cancel\">Отмена</button>
            <button class=\"btn btn-primary\" id=\"modal-save\">Сохранить</button>
        </div>
    </div>
</div>

<!-- New Page Modal -->
<div class=\"modal-overlay\" id=\"new-page-modal\" style=\"display: none;\">
    <div class=\"modal modal-sm\">
        <div class=\"modal-header\">
            <h3>Новая страница</h3>
            <button class=\"modal-close\" id=\"new-page-modal-close\">&times;</button>
        </div>
        <div class=\"modal-body\">
            <div class=\"form-group\">
                <label>Название страницы</label>
                <input type=\"text\" id=\"new-page-title-input\" class=\"form-input\" placeholder=\"Введите название...\" autofocus>
            </div>
        </div>
        <div class=\"modal-footer\">
            <button class=\"btn btn-secondary\" id=\"new-page-modal-cancel\">Отмена</button>
            <button class=\"btn btn-primary\" id=\"new-page-modal-save\">Создать</button>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div class=\"context-menu\" id=\"context-menu\" style=\"display: none;\">
    <ul class=\"context-menu-list\">
        <li data-action=\"add-child\"><i class=\"fas fa-plus\"></i> Добавить подзадачу</li>
        <li data-action=\"add-above\"><i class=\"fas fa-arrow-up\"></i> Добавить сверху</li>
        <li data-action=\"add-below\"><i class=\"fas fa-arrow-down\"></i> Добавить снизу</li>
        <li class=\"divider\"></li>
        <li data-action=\"edit\"><i class=\"fas fa-edit\"></i> Редактировать</li>
        <li data-action=\"set-status\"><i class=\"fas fa-check-circle\"></i> Установить статус</li>
        <li data-action=\"set-priority\"><i class=\"fas fa-exclamation-triangle\"></i> Приоритет</li>
        <li class=\"divider\"></li>
        <li data-action=\"delete\" class=\"danger\"><i class=\"fas fa-trash\"></i> Удалить</li>
    </ul>
</div>
";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    // line 132
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_javascripts(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "javascripts"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "javascripts"));

        // line 133
        yield "<script>
let currentProjectId = null;
let pages = [];

// ==========================================
// Auth Check & Init
// ==========================================

document.addEventListener(\x27DOMContentLoaded\x27, function() {
    const token = localStorage.getItem(\x27auth_token\x27);
    if (!token) {
        window.location.href = \x27/login\x27;
        return;
    }

    apiRequest(\x27/me\x27).then(function(response) {
        document.getElementById(\x27user-name\x27).textContent = response.user.name || response.user.email;
        loadProjects();
    }).catch(function() {
        localStorage.removeItem(\x27auth_token\x27);
        window.location.href = \x27/login\x27;
    });

    document.getElementById(\x27logout-btn\x27).addEventListener(\x27click\x27, function() {
        localStorage.removeItem(\x27auth_token\x27);
        window.location.href = \x27/login\x27;
    });

    document.getElementById(\x27project-selector\x27).addEventListener(\x27change\x27, function() {
        currentProjectId = parseInt(this.value);
        if (currentProjectId) {
            loadMainPage();
        }
    });

    document.getElementById(\x27back-to-main\x27).addEventListener(\x27click\x27, function() {
        closeFullEditor();
    });

    document.getElementById(\x27add-task-btn\x27).addEventListener(\x27click\x27, function() {
        var container = document.getElementById(\x27tasks-container\x27);
        addNewTask(parseInt(container.dataset.pageId));
    });

    document.getElementById(\x27new-page-btn\x27).addEventListener(\x27click\x27, function() {
        showNewPageModal();
    });

    document.getElementById(\x27new-page-modal-close\x27).addEventListener(\x27click\x27, function() {
        closeNewPageModal();
    });

    document.getElementById(\x27new-page-modal-cancel\x27).addEventListener(\x27click\x27, function() {
        closeNewPageModal();
    });

    document.getElementById(\x27new-page-modal-save\x27).addEventListener(\x27click\x27, function() {
        confirmNewPage();
    });

    document.getElementById(\x27new-page-title-input\x27).addEventListener(\x27keydown\x27, function(e) {
        if (e.key === \x27Enter\x27) {
            e.preventDefault();
            confirmNewPage();
        }
    });

    document.getElementById(\x27modal-close\x27).addEventListener(\x27click\x27, function() {
        closeModal();
    });

    document.getElementById(\x27modal-cancel\x27).addEventListener(\x27click\x27, function() {
        closeModal();
    });

    document.getElementById(\x27modal-save\x27).addEventListener(\x27click\x27, function() {
        saveTaskFromModal();
    });

    document.addEventListener(\x27click\x27, function() {
        document.getElementById(\x27context-menu\x27).style.display = \x27none\x27;
    });

    document.querySelectorAll(\x27#context-menu li[data-action]\x27).forEach(function(li) {
        li.addEventListener(\x27click\x27, function(e) {
            e.stopPropagation();
            handleContextAction(li.dataset.action);
            document.getElementById(\x27context-menu\x27).style.display = \x27none\x27;
        });
    });

    // Auto-save page title on input (delegated)
    document.addEventListener(\x27input\x27, function(e) {
        if (e.target.id === \x27full-page-title\x27) {
            var title = e.target.textContent.trim();
            var container = document.getElementById(\x27tasks-container\x27);
            var pageId = parseInt(container.dataset.pageId);
            if (title && pageId) {
                debouncedSave(\x27page-title\x27, function() {
                    apiRequest(\x27/pages/\x27 + pageId, \x27PUT\x27, { title: title });
                });
            }
        }
    });
});

// ==========================================
// Projects
// ==========================================

function loadProjects() {
    apiRequest(\x27/projects\x27).then(function(response) {
        var selector = document.getElementById(\x27project-selector\x27);
        selector.innerHTML = \x27\x27;
        var defaultOption = document.createElement(\x27option\x27);
        defaultOption.value = \x27\x27;
        defaultOption.textContent = \x27Выберите проект...\x27;
        selector.appendChild(defaultOption);

        response.projects.forEach(function(project) {
            var opt = document.createElement(\x27option\x27);
            opt.value = project.id;
            opt.textContent = project.name;
            selector.appendChild(opt);
        });

        if (response.currentProjectId) {
            selector.value = response.currentProjectId;
            currentProjectId = response.currentProjectId;
            loadMainPage();
        }
    }).catch(function(xhr) {
        console.error(\x27Failed to load projects:\x27, xhr);
    });
}

// ==========================================
// New Page
// ==========================================

function showNewPageModal() {
    if (!currentProjectId) return;
    document.getElementById(\x27new-page-title-input\x27).value = \x27\x27;
    document.getElementById(\x27new-page-modal\x27).style.display = \x27flex\x27;
    setTimeout(function() {
        document.getElementById(\x27new-page-title-input\x27).focus();
    }, 100);
}

function closeNewPageModal() {
    document.getElementById(\x27new-page-modal\x27).style.display = \x27none\x27;
}

function confirmNewPage() {
    var title = document.getElementById(\x27new-page-title-input\x27).value.trim();
    if (!title) {
        document.getElementById(\x27new-page-title-input\x27).focus();
        return;
    }
    closeNewPageModal();
    apiRequest(\x27/projects/\x27 + currentProjectId + \x27/pages\x27, \x27POST\x27, {
        title: title
    }).then(function(response) {
        window.location.href = \x27/page/\x27 + response.page.id;
    }).catch(function(xhr) {
        console.error(\x27Failed to create page:\x27, xhr);
    });
}

// ==========================================
// Main Page - Pages Grid
// ==========================================

function loadMainPage() {
    if (!currentProjectId) return;

    apiRequest(\x27/projects/\x27 + currentProjectId + \x27/pages\x27).then(function(response) {
        pages = response.pages;
        renderPagesGrid();
    }).catch(function(xhr) {
        console.error(\x27Failed to load pages:\x27, xhr);
    });
}

function renderPagesGrid() {
    var grid = document.getElementById(\x27pages-grid\x27);
    grid.innerHTML = \x27\x27;

    if (pages.length === 0) {
        grid.innerHTML = \x27<div class=\"empty-state\"><i class=\"fas fa-file-alt fa-3x\"></i><p>Нет страниц</p></div>\x27;
        return;
    }

    var twoWeeksAgo = new Date();
    twoWeeksAgo.setDate(twoWeeksAgo.getDate() - 14);

    var expandedCount = 0;

    pages.forEach(function(page) {
        var editedAt = page.editedAt ? new Date(page.editedAt) : new Date(page.createdAt);
        var isRecent = editedAt >= twoWeeksAgo;
        var shouldExpand = isRecent && expandedCount < 3;

        if (shouldExpand) expandedCount++;

        var card = document.createElement(\x27div\x27);
        card.className = \x27page-card \x27 + (shouldExpand ? \x27expanded\x27 : \x27collapsed\x27);
        card.dataset.pageId = page.id;

        // Header with link to /page/{id}
        var header = document.createElement(\x27div\x27);
        header.className = \x27page-card-header\x27;

        var titleLink = document.createElement(\x27a\x27);
        titleLink.href = \x27/page/\x27 + page.id;
        titleLink.className = \x27page-card-title\x27;
        titleLink.textContent = page.title;
        header.appendChild(titleLink);

        var dateStr = editedAt.toLocaleDateString(\x27ru-RU\x27, { day: \x27numeric\x27, month: \x27long\x27, year: \x27numeric\x27 });
        var dateSpan = document.createElement(\x27span\x27);
        dateSpan.className = \x27page-card-date\x27;
        dateSpan.textContent = dateStr;
        header.appendChild(dateSpan);

        card.appendChild(header);

        // Inline editor (only for expanded)
        if (shouldExpand) {
            var editorContainer = document.createElement(\x27div\x27);
            editorContainer.className = \x27page-card-editor\x27;
            card.appendChild(editorContainer);
            var pageTasks = (page.tasks || []).slice(0, 10);
            renderInlineTasks(pageTasks, editorContainer, page.id);
        }

        grid.appendChild(card);
    });

    // Make sure sortable is set up after all cards are in DOM
    makeSortable();
}

// ==========================================
// Full Page Editor
// ==========================================

function openFullEditor(pageId) {
    document.getElementById(\x27pages-grid\x27).style.display = \x27none\x27;
    document.getElementById(\x27full-editor\x27).style.display = \x27block\x27;
    document.getElementById(\x27tasks-container\x27).dataset.pageId = pageId;

    apiRequest(\x27/pages/\x27 + pageId).then(function(response) {
        document.getElementById(\x27full-page-title\x27).textContent = response.page.title;
        statuses = response.statuses || [];
        renderInlineTasks(response.tasks || [], document.getElementById(\x27tasks-container\x27), pageId);
    }).catch(function(xhr) {
        console.error(\x27Failed to load page:\x27, xhr);
    });
}

function closeFullEditor() {
    document.getElementById(\x27full-editor\x27).style.display = \x27none\x27;
    document.getElementById(\x27pages-grid\x27).style.display = \x27\x27;
    loadMainPage();
}
</script>
";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "index.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  250 => 133,  237 => 132,  102 => 6,  89 => 5,  66 => 3,  43 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \x27base.html.twig\x27 %}

{% block title %}Checker{% endblock %}

{% block body %}
<div id=\"app\">
    <!-- Header -->
    <header class=\"header\">
        <div class=\"header-left\">
            <a href=\"/\" class=\"header-title\">Checker</a>
        </div>
        <div class=\"header-center\">
            <select id=\"project-selector\" class=\"project-selector\">
            </select>
        </div>
        <div class=\"header-right\">
            <span id=\"user-name\" class=\"user-name\"></span>
            <button id=\"logout-btn\" class=\"btn btn-sm btn-outline\" title=\"Выйти\">
                <i class=\"fas fa-sign-out-alt\"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class=\"main-content\">
        <div class=\"pages-grid\" id=\"pages-grid\">
            <div class=\"loading-pages\">Загрузка...</div>
        </div>
        <div class=\"new-page-bottom\">
            <button id=\"new-page-btn\" class=\"btn btn-primary\" title=\"Новая страница\">
                <i class=\"fas fa-plus\"></i> Страница
            </button>
        </div>
    </main>
</div>

<!-- Full Page Editor (hidden by default) -->
<div id=\"full-editor\" class=\"full-editor\" style=\"display: none;\">
    <div class=\"full-editor-header\">
        <button id=\"back-to-main\" class=\"btn btn-sm btn-outline\">
            <i class=\"fas fa-arrow-left\"></i> Назад
        </button>
        <h2 id=\"full-page-title\" contenteditable=\"true\" class=\"page-title-editable\"></h2>
        <div class=\"editor-actions\">
            <button id=\"add-task-btn\" class=\"btn btn-sm btn-primary\" title=\"Добавить задачу\">
                <i class=\"fas fa-plus\"></i> Задача
            </button>
        </div>
    </div>
    <div class=\"full-editor-body\">
        <div class=\"tasks-container\" id=\"tasks-container\"></div>
    </div>
</div>

<!-- Task Modal -->
<div class=\"modal-overlay\" id=\"task-modal\" style=\"display: none;\">
    <div class=\"modal\">
        <div class=\"modal-header\">
            <h3 id=\"modal-title\">Редактирование задачи</h3>
            <button class=\"modal-close\" id=\"modal-close\">&times;</button>
        </div>
        <div class=\"modal-body\">
            <div class=\"form-group\">
                <label>Текст задачи</label>
                <div id=\"modal-task-text\" contenteditable=\"true\" class=\"modal-editable\"></div>
            </div>
            <div class=\"form-group\">
                <label>Описание</label>
                <textarea id=\"modal-task-description\" class=\"modal-textarea\" rows=\"4\"></textarea>
            </div>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>Статус</label>
                    <select id=\"modal-task-status\" class=\"form-select\"></select>
                </div>
                <div class=\"form-group\">
                    <label>Приоритет</label>
                    <select id=\"modal-task-priority\" class=\"form-select\">
                        <option value=\"0\">Обычный</option>
                        <option value=\"1\">Высокий</option>
                    </select>
                </div>
            </div>
            <div class=\"form-group\">
                <label>Ответственный</label>
                <input type=\"text\" id=\"modal-task-assignee\" class=\"form-input\" placeholder=\"Email ответственного\">
            </div>
        </div>
        <div class=\"modal-footer\">
            <button class=\"btn btn-secondary\" id=\"modal-cancel\">Отмена</button>
            <button class=\"btn btn-primary\" id=\"modal-save\">Сохранить</button>
        </div>
    </div>
</div>

<!-- New Page Modal -->
<div class=\"modal-overlay\" id=\"new-page-modal\" style=\"display: none;\">
    <div class=\"modal modal-sm\">
        <div class=\"modal-header\">
            <h3>Новая страница</h3>
            <button class=\"modal-close\" id=\"new-page-modal-close\">&times;</button>
        </div>
        <div class=\"modal-body\">
            <div class=\"form-group\">
                <label>Название страницы</label>
                <input type=\"text\" id=\"new-page-title-input\" class=\"form-input\" placeholder=\"Введите название...\" autofocus>
            </div>
        </div>
        <div class=\"modal-footer\">
            <button class=\"btn btn-secondary\" id=\"new-page-modal-cancel\">Отмена</button>
            <button class=\"btn btn-primary\" id=\"new-page-modal-save\">Создать</button>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div class=\"context-menu\" id=\"context-menu\" style=\"display: none;\">
    <ul class=\"context-menu-list\">
        <li data-action=\"add-child\"><i class=\"fas fa-plus\"></i> Добавить подзадачу</li>
        <li data-action=\"add-above\"><i class=\"fas fa-arrow-up\"></i> Добавить сверху</li>
        <li data-action=\"add-below\"><i class=\"fas fa-arrow-down\"></i> Добавить снизу</li>
        <li class=\"divider\"></li>
        <li data-action=\"edit\"><i class=\"fas fa-edit\"></i> Редактировать</li>
        <li data-action=\"set-status\"><i class=\"fas fa-check-circle\"></i> Установить статус</li>
        <li data-action=\"set-priority\"><i class=\"fas fa-exclamation-triangle\"></i> Приоритет</li>
        <li class=\"divider\"></li>
        <li data-action=\"delete\" class=\"danger\"><i class=\"fas fa-trash\"></i> Удалить</li>
    </ul>
</div>
{% endblock %}

{% block javascripts %}
<script>
let currentProjectId = null;
let pages = [];

// ==========================================
// Auth Check & Init
// ==========================================

document.addEventListener(\x27DOMContentLoaded\x27, function() {
    const token = localStorage.getItem(\x27auth_token\x27);
    if (!token) {
        window.location.href = \x27/login\x27;
        return;
    }

    apiRequest(\x27/me\x27).then(function(response) {
        document.getElementById(\x27user-name\x27).textContent = response.user.name || response.user.email;
        loadProjects();
    }).catch(function() {
        localStorage.removeItem(\x27auth_token\x27);
        window.location.href = \x27/login\x27;
    });

    document.getElementById(\x27logout-btn\x27).addEventListener(\x27click\x27, function() {
        localStorage.removeItem(\x27auth_token\x27);
        window.location.href = \x27/login\x27;
    });

    document.getElementById(\x27project-selector\x27).addEventListener(\x27change\x27, function() {
        currentProjectId = parseInt(this.value);
        if (currentProjectId) {
            loadMainPage();
        }
    });

    document.getElementById(\x27back-to-main\x27).addEventListener(\x27click\x27, function() {
        closeFullEditor();
    });

    document.getElementById(\x27add-task-btn\x27).addEventListener(\x27click\x27, function() {
        var container = document.getElementById(\x27tasks-container\x27);
        addNewTask(parseInt(container.dataset.pageId));
    });

    document.getElementById(\x27new-page-btn\x27).addEventListener(\x27click\x27, function() {
        showNewPageModal();
    });

    document.getElementById(\x27new-page-modal-close\x27).addEventListener(\x27click\x27, function() {
        closeNewPageModal();
    });

    document.getElementById(\x27new-page-modal-cancel\x27).addEventListener(\x27click\x27, function() {
        closeNewPageModal();
    });

    document.getElementById(\x27new-page-modal-save\x27).addEventListener(\x27click\x27, function() {
        confirmNewPage();
    });

    document.getElementById(\x27new-page-title-input\x27).addEventListener(\x27keydown\x27, function(e) {
        if (e.key === \x27Enter\x27) {
            e.preventDefault();
            confirmNewPage();
        }
    });

    document.getElementById(\x27modal-close\x27).addEventListener(\x27click\x27, function() {
        closeModal();
    });

    document.getElementById(\x27modal-cancel\x27).addEventListener(\x27click\x27, function() {
        closeModal();
    });

    document.getElementById(\x27modal-save\x27).addEventListener(\x27click\x27, function() {
        saveTaskFromModal();
    });

    document.addEventListener(\x27click\x27, function() {
        document.getElementById(\x27context-menu\x27).style.display = \x27none\x27;
    });

    document.querySelectorAll(\x27#context-menu li[data-action]\x27).forEach(function(li) {
        li.addEventListener(\x27click\x27, function(e) {
            e.stopPropagation();
            handleContextAction(li.dataset.action);
            document.getElementById(\x27context-menu\x27).style.display = \x27none\x27;
        });
    });

    // Auto-save page title on input (delegated)
    document.addEventListener(\x27input\x27, function(e) {
        if (e.target.id === \x27full-page-title\x27) {
            var title = e.target.textContent.trim();
            var container = document.getElementById(\x27tasks-container\x27);
            var pageId = parseInt(container.dataset.pageId);
            if (title && pageId) {
                debouncedSave(\x27page-title\x27, function() {
                    apiRequest(\x27/pages/\x27 + pageId, \x27PUT\x27, { title: title });
                });
            }
        }
    });
});

// ==========================================
// Projects
// ==========================================

function loadProjects() {
    apiRequest(\x27/projects\x27).then(function(response) {
        var selector = document.getElementById(\x27project-selector\x27);
        selector.innerHTML = \x27\x27;
        var defaultOption = document.createElement(\x27option\x27);
        defaultOption.value = \x27\x27;
        defaultOption.textContent = \x27Выберите проект...\x27;
        selector.appendChild(defaultOption);

        response.projects.forEach(function(project) {
            var opt = document.createElement(\x27option\x27);
            opt.value = project.id;
            opt.textContent = project.name;
            selector.appendChild(opt);
        });

        if (response.currentProjectId) {
            selector.value = response.currentProjectId;
            currentProjectId = response.currentProjectId;
            loadMainPage();
        }
    }).catch(function(xhr) {
        console.error(\x27Failed to load projects:\x27, xhr);
    });
}

// ==========================================
// New Page
// ==========================================

function showNewPageModal() {
    if (!currentProjectId) return;
    document.getElementById(\x27new-page-title-input\x27).value = \x27\x27;
    document.getElementById(\x27new-page-modal\x27).style.display = \x27flex\x27;
    setTimeout(function() {
        document.getElementById(\x27new-page-title-input\x27).focus();
    }, 100);
}

function closeNewPageModal() {
    document.getElementById(\x27new-page-modal\x27).style.display = \x27none\x27;
}

function confirmNewPage() {
    var title = document.getElementById(\x27new-page-title-input\x27).value.trim();
    if (!title) {
        document.getElementById(\x27new-page-title-input\x27).focus();
        return;
    }
    closeNewPageModal();
    apiRequest(\x27/projects/\x27 + currentProjectId + \x27/pages\x27, \x27POST\x27, {
        title: title
    }).then(function(response) {
        window.location.href = \x27/page/\x27 + response.page.id;
    }).catch(function(xhr) {
        console.error(\x27Failed to create page:\x27, xhr);
    });
}

// ==========================================
// Main Page - Pages Grid
// ==========================================

function loadMainPage() {
    if (!currentProjectId) return;

    apiRequest(\x27/projects/\x27 + currentProjectId + \x27/pages\x27).then(function(response) {
        pages = response.pages;
        renderPagesGrid();
    }).catch(function(xhr) {
        console.error(\x27Failed to load pages:\x27, xhr);
    });
}

function renderPagesGrid() {
    var grid = document.getElementById(\x27pages-grid\x27);
    grid.innerHTML = \x27\x27;

    if (pages.length === 0) {
        grid.innerHTML = \x27<div class=\"empty-state\"><i class=\"fas fa-file-alt fa-3x\"></i><p>Нет страниц</p></div>\x27;
        return;
    }

    var twoWeeksAgo = new Date();
    twoWeeksAgo.setDate(twoWeeksAgo.getDate() - 14);

    var expandedCount = 0;

    pages.forEach(function(page) {
        var editedAt = page.editedAt ? new Date(page.editedAt) : new Date(page.createdAt);
        var isRecent = editedAt >= twoWeeksAgo;
        var shouldExpand = isRecent && expandedCount < 3;

        if (shouldExpand) expandedCount++;

        var card = document.createElement(\x27div\x27);
        card.className = \x27page-card \x27 + (shouldExpand ? \x27expanded\x27 : \x27collapsed\x27);
        card.dataset.pageId = page.id;

        // Header with link to /page/{id}
        var header = document.createElement(\x27div\x27);
        header.className = \x27page-card-header\x27;

        var titleLink = document.createElement(\x27a\x27);
        titleLink.href = \x27/page/\x27 + page.id;
        titleLink.className = \x27page-card-title\x27;
        titleLink.textContent = page.title;
        header.appendChild(titleLink);

        var dateStr = editedAt.toLocaleDateString(\x27ru-RU\x27, { day: \x27numeric\x27, month: \x27long\x27, year: \x27numeric\x27 });
        var dateSpan = document.createElement(\x27span\x27);
        dateSpan.className = \x27page-card-date\x27;
        dateSpan.textContent = dateStr;
        header.appendChild(dateSpan);

        card.appendChild(header);

        // Inline editor (only for expanded)
        if (shouldExpand) {
            var editorContainer = document.createElement(\x27div\x27);
            editorContainer.className = \x27page-card-editor\x27;
            card.appendChild(editorContainer);
            var pageTasks = (page.tasks || []).slice(0, 10);
            renderInlineTasks(pageTasks, editorContainer, page.id);
        }

        grid.appendChild(card);
    });

    // Make sure sortable is set up after all cards are in DOM
    makeSortable();
}

// ==========================================
// Full Page Editor
// ==========================================

function openFullEditor(pageId) {
    document.getElementById(\x27pages-grid\x27).style.display = \x27none\x27;
    document.getElementById(\x27full-editor\x27).style.display = \x27block\x27;
    document.getElementById(\x27tasks-container\x27).dataset.pageId = pageId;

    apiRequest(\x27/pages/\x27 + pageId).then(function(response) {
        document.getElementById(\x27full-page-title\x27).textContent = response.page.title;
        statuses = response.statuses || [];
        renderInlineTasks(response.tasks || [], document.getElementById(\x27tasks-container\x27), pageId);
    }).catch(function(xhr) {
        console.error(\x27Failed to load page:\x27, xhr);
    });
}

function closeFullEditor() {
    document.getElementById(\x27full-editor\x27).style.display = \x27none\x27;
    document.getElementById(\x27pages-grid\x27).style.display = \x27\x27;
    loadMainPage();
}
</script>
{% endblock %}", "index.html.twig", "/work/checker/templates/index.html.twig");
    }
}
