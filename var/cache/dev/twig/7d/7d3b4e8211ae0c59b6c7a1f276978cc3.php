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

/* page.html.twig */
class __TwigTemplate_8552953caba456e979c4b62633cd9454 extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "page.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "page.html.twig"));

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

        yield "Checker — Редактор";
        
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
            <a href=\"/\" class=\"btn btn-sm btn-outline\" id=\"back-to-main\">
                <i class=\"fas fa-arrow-left\"></i> Назад
            </a>
        </div>
        <div class=\"header-center\">
            <h2 id=\"full-page-title\" contenteditable=\"true\" class=\"page-title-editable\"></h2>
        </div>
        <div class=\"header-right\">
            <button id=\"add-task-btn\" class=\"btn btn-sm btn-primary\" title=\"Добавить задачу\">
                <i class=\"fas fa-plus\"></i> Задача
            </button>
        </div>
    </header>

    <!-- Editor Body -->
    <main class=\"main-content\">
        <div class=\"tasks-container\" id=\"tasks-container\"></div>
    </main>
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

    // line 87
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

        // line 88
        yield "<script>
const PAGE_ID = ";
        // line 89
        yield (string) $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["pageId"]) || array_key_exists("pageId", $context) ? $context["pageId"] : (function () { throw new RuntimeError('Variable "pageId" does not exist.', 89, $this->source); })()), "html", null, true);
        yield ";

// ==========================================
// Init
// ==========================================

document.addEventListener(\x27DOMContentLoaded\x27, function() {
    const token = localStorage.getItem(\x27auth_token\x27);
    if (!token) {
        window.location.href = \x27/login\x27;
        return;
    }

    loadPage();

    document.getElementById(\x27add-task-btn\x27).addEventListener(\x27click\x27, function() {
        addNewTask(PAGE_ID);
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

    // Auto-save page title on input
    document.addEventListener(\x27input\x27, function(e) {
        if (e.target.id === \x27full-page-title\x27) {
            var title = e.target.textContent.trim();
            if (title) {
                debouncedSave(\x27page-title\x27, function() {
                    apiRequest(\x27/pages/\x27 + PAGE_ID, \x27PUT\x27, { title: title });
                });
            }
        }
    });
});

// ==========================================
// Page Loading
// ==========================================

function loadPage() {
    apiRequest(\x27/pages/\x27 + PAGE_ID).then(function(response) {
        document.getElementById(\x27full-page-title\x27).textContent = response.page.title;
        statuses = response.statuses || [];
        renderInlineTasks(response.tasks || [], document.getElementById(\x27tasks-container\x27), PAGE_ID);
    }).catch(function(xhr) {
        console.error(\x27Failed to load page:\x27, xhr);
        if (xhr.status === 401) {
            localStorage.removeItem(\x27auth_token\x27);
            window.location.href = \x27/login\x27;
        } else if (xhr.status === 404) {
            document.getElementById(\x27tasks-container\x27).innerHTML = \x27<div class=\"text-muted\" style=\"text-align:center;padding:40px;\">Страница не найдена</div>\x27;
        }
    });
}

window.reloadTasks = function(response) {
    statuses = response.statuses || [];
    renderInlineTasks(response.tasks || [], document.getElementById(\x27tasks-container\x27), PAGE_ID);
};
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
        return "page.html.twig";
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
        return array (  208 => 89,  205 => 88,  192 => 87,  102 => 6,  89 => 5,  66 => 3,  43 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends \x27base.html.twig\x27 %}

{% block title %}Checker — Редактор{% endblock %}

{% block body %}
<div id=\"app\">
    <!-- Header -->
    <header class=\"header\">
        <div class=\"header-left\">
            <a href=\"/\" class=\"btn btn-sm btn-outline\" id=\"back-to-main\">
                <i class=\"fas fa-arrow-left\"></i> Назад
            </a>
        </div>
        <div class=\"header-center\">
            <h2 id=\"full-page-title\" contenteditable=\"true\" class=\"page-title-editable\"></h2>
        </div>
        <div class=\"header-right\">
            <button id=\"add-task-btn\" class=\"btn btn-sm btn-primary\" title=\"Добавить задачу\">
                <i class=\"fas fa-plus\"></i> Задача
            </button>
        </div>
    </header>

    <!-- Editor Body -->
    <main class=\"main-content\">
        <div class=\"tasks-container\" id=\"tasks-container\"></div>
    </main>
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
const PAGE_ID = {{ pageId }};

// ==========================================
// Init
// ==========================================

document.addEventListener(\x27DOMContentLoaded\x27, function() {
    const token = localStorage.getItem(\x27auth_token\x27);
    if (!token) {
        window.location.href = \x27/login\x27;
        return;
    }

    loadPage();

    document.getElementById(\x27add-task-btn\x27).addEventListener(\x27click\x27, function() {
        addNewTask(PAGE_ID);
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

    // Auto-save page title on input
    document.addEventListener(\x27input\x27, function(e) {
        if (e.target.id === \x27full-page-title\x27) {
            var title = e.target.textContent.trim();
            if (title) {
                debouncedSave(\x27page-title\x27, function() {
                    apiRequest(\x27/pages/\x27 + PAGE_ID, \x27PUT\x27, { title: title });
                });
            }
        }
    });
});

// ==========================================
// Page Loading
// ==========================================

function loadPage() {
    apiRequest(\x27/pages/\x27 + PAGE_ID).then(function(response) {
        document.getElementById(\x27full-page-title\x27).textContent = response.page.title;
        statuses = response.statuses || [];
        renderInlineTasks(response.tasks || [], document.getElementById(\x27tasks-container\x27), PAGE_ID);
    }).catch(function(xhr) {
        console.error(\x27Failed to load page:\x27, xhr);
        if (xhr.status === 401) {
            localStorage.removeItem(\x27auth_token\x27);
            window.location.href = \x27/login\x27;
        } else if (xhr.status === 404) {
            document.getElementById(\x27tasks-container\x27).innerHTML = \x27<div class=\"text-muted\" style=\"text-align:center;padding:40px;\">Страница не найдена</div>\x27;
        }
    });
}

window.reloadTasks = function(response) {
    statuses = response.statuses || [];
    renderInlineTasks(response.tasks || [], document.getElementById(\x27tasks-container\x27), PAGE_ID);
};
</script>
{% endblock %}", "page.html.twig", "/work/checker/templates/page.html.twig");
    }
}
