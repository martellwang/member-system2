<?php
/**
 * 路由定義
 * $router->get(路徑, Controller, Action)
 * $router->post(路徑, Controller, Action)
 */

// ── 前台頁面 ──────────────────────────────────
$router->get('',                          'MemberController', 'registerPage');
$router->get('register',                  'MemberController', 'registerPage');
$router->get('verify/{token}',            'MemberController', 'verify');

// ── 後台頁面 ──────────────────────────────────
$router->get('admin',                     'AdminController',  'index');

// ── API：會員 ─────────────────────────────────
$router->post('api/members/register',     'MemberController', 'register');

// ── API：後台 ─────────────────────────────────
$router->get('api/admin/members',         'AdminController',  'list');
$router->get('api/admin/stats',           'AdminController',  'stats');
$router->post('api/admin/members/{id}/approve', 'AdminController', 'approve');
$router->post('api/admin/members/{id}/suspend', 'AdminController', 'suspend');
$router->post('api/admin/members/{id}/delete',  'AdminController', 'destroy');
