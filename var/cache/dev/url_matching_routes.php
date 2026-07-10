<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/login' => [[['_route' => 'app_login', '_controller' => 'App\\Controller\\AppController::login'], null, null, null, false, false, null]],
        '/' => [[['_route' => 'app_index', '_controller' => 'App\\Controller\\AppController::index'], null, null, null, false, false, null]],
        '/api/register' => [[['_route' => 'api_register', '_controller' => 'App\\Controller\\AuthController::register'], null, ['POST' => 0], null, false, false, null]],
        '/api/login' => [[['_route' => 'api_login', '_controller' => 'App\\Controller\\AuthController::login'], null, ['POST' => 0], null, false, false, null]],
        '/api/me' => [[['_route' => 'api_me', '_controller' => 'App\\Controller\\AuthController::me'], null, ['GET' => 0], null, false, false, null]],
        '/api/projects' => [[['_route' => 'api_projects_list', '_controller' => 'App\\Controller\\ProjectController::list'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/page/(\\d+)(*:18)'
                .'|/api/(?'
                    .'|p(?'
                        .'|ages/([^/]++)(?'
                            .'|(*:53)'
                            .'|/tasks(*:66)'
                        .')'
                        .'|rojects/([^/]++)/pages(?'
                            .'|(*:99)'
                        .')'
                    .')'
                    .'|tasks/([^/]++)(?'
                        .'|(*:125)'
                        .'|/(?'
                            .'|status(*:143)'
                            .'|move(*:155)'
                        .')'
                        .'|(*:164)'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        18 => [[['_route' => 'app_page', '_controller' => 'App\\Controller\\AppController::page'], ['id'], null, null, false, true, null]],
        53 => [
            [['_route' => 'api_page_get', '_controller' => 'App\\Controller\\PageController::get'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_page_update', '_controller' => 'App\\Controller\\PageController::update'], ['id'], ['PUT' => 0], null, false, true, null],
        ],
        66 => [[['_route' => 'api_page_tasks_create', '_controller' => 'App\\Controller\\PageController::createTask'], ['id'], ['POST' => 0], null, false, false, null]],
        99 => [
            [['_route' => 'api_project_pages', '_controller' => 'App\\Controller\\ProjectController::pages'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_project_pages_create', '_controller' => 'App\\Controller\\ProjectController::createPage'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        125 => [
            [['_route' => 'api_task_get', '_controller' => 'App\\Controller\\TaskController::get'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_task_update', '_controller' => 'App\\Controller\\TaskController::update'], ['id'], ['PUT' => 0], null, false, true, null],
        ],
        143 => [[['_route' => 'api_task_status', '_controller' => 'App\\Controller\\TaskController::updateStatus'], ['id'], ['PUT' => 0], null, false, false, null]],
        155 => [[['_route' => 'api_task_move', '_controller' => 'App\\Controller\\TaskController::move'], ['id'], ['PUT' => 0], null, false, false, null]],
        164 => [
            [['_route' => 'api_task_delete', '_controller' => 'App\\Controller\\TaskController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
