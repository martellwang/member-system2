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
$router->get('auth/google',               'MemberController', 'googleStart');
$router->get('auth/google/callback',      'MemberController', 'googleCallback');

// ── 後台頁面 ──────────────────────────────────
$router->get('admin/login',               'AdminController',  'loginPage');
$router->get('admin',                     'AdminController',  'index');
$router->get('admin/members/{id}/edit',   'AdminController',  'edit');

// ── API：會員 ─────────────────────────────────
$router->post('api/members/register',     'MemberController', 'register');

// ── API：後台 ─────────────────────────────────
$router->post('api/admin/login',          'AdminController',  'login');
$router->post('api/admin/logout',         'AdminController',  'logout');
$router->get('api/admin/members',         'AdminController',  'list');
$router->get('api/admin/stats',           'AdminController',  'stats');
$router->get('api/admin/members/{id}/id-documents/{side}', 'AdminController', 'idDocument');
$router->post('api/admin/members/{id}/update',  'AdminController', 'update');
$router->post('api/admin/members/{id}/approve', 'AdminController', 'approve');
$router->post('api/admin/members/{id}/suspend', 'AdminController', 'suspend');
$router->post('api/admin/members/{id}/delete',  'AdminController', 'destroy');
