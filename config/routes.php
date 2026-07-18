<?php
/**
 * 路由定義
 * $router->get(路徑, Controller, Action)
 * $router->post(路徑, Controller, Action)
 */

// ── 前台頁面 ──────────────────────────────────
$router->get('',                          'HomeController', 'index');
$router->get('register',                  'MemberController', 'registerPage');
$router->get('register/complete',         'MemberController', 'registerCompletePage');
$router->get('login',                     'MemberAuthController', 'loginPage');
$router->get('member',                    'MemberAuthController', 'dashboard');
$router->get('member/logout',             'MemberAuthController', 'logout');
$router->get('verify/{token}',            'MemberController', 'verify');
$router->get('auth/google',               'MemberController', 'googleStart');
$router->get('auth/google/callback',      'MemberController', 'googleCallback');

// ── 後台頁面 ──────────────────────────────────
$router->get('admin/login',               'AdminController',  'loginPage');
$router->get('admin/setup-password/{token}', 'AdminController', 'setupPasswordPage');
$router->get('admin',                     'AdminController',  'index');
$router->get('admin/staff',               'AdminController',  'staffPage');
$router->get('admin/staff/{id}/edit',     'AdminController',  'staffEditPage');
$router->get('admin/members/{id}/edit',   'AdminController',  'edit');

// ── API：會員 ─────────────────────────────────
$router->post('api/members/register',     'MemberController', 'register');
$router->post('api/members/setup-password/{token}', 'MemberController', 'setupPassword');
$router->post('api/members/login',        'MemberAuthController', 'login');
$router->post('api/members/logout',       'MemberAuthController', 'logoutApi');
$router->get('api/members/session-status', 'MemberAuthController', 'sessionStatus');
$router->post('api/members/session-touch', 'MemberAuthController', 'sessionTouch');
$router->post('api/members/stores',       'MemberAuthController', 'storeCreate');

// ── API：後台 ─────────────────────────────────
$router->post('api/admin/login',          'AdminController',  'login');
$router->post('api/admin/setup-password/{token}', 'AdminController', 'setupPassword');
$router->post('api/admin/logout',         'AdminController',  'logout');
$router->get('api/admin/session-status',  'AdminController',  'sessionStatus');
$router->post('api/admin/session-touch',  'AdminController',  'sessionTouch');
$router->get('api/admin/members',         'AdminController',  'list');
$router->get('api/admin/stats',           'AdminController',  'stats');
$router->get('api/admin/staff',           'AdminController',  'staffList');
$router->post('api/admin/staff/create',   'AdminController',  'staffCreate');
$router->post('api/admin/staff/{id}/update', 'AdminController', 'staffUpdate');
$router->post('api/admin/staff/{id}/delete', 'AdminController', 'staffDelete');
$router->get('api/admin/staff/{id}/login-logs', 'AdminController', 'staffLoginLogs');
$router->get('api/admin/settings/security', 'AdminController', 'securitySettings');
$router->post('api/admin/settings/security', 'AdminController', 'updateSecuritySettings');
$router->get('api/admin/members/{id}/id-documents/{side}', 'AdminController', 'idDocument');
$router->post('api/admin/members/{id}/update',  'AdminController', 'update');
$router->post('api/admin/members/{memberId}/stores/{storeId}/update', 'AdminController', 'updateMemberStore');
$router->post('api/admin/members/{id}/resend-verification', 'AdminController', 'resendMemberVerification');
$router->post('api/admin/members/{id}/approve', 'AdminController', 'approve');
$router->post('api/admin/members/{id}/suspend', 'AdminController', 'suspend');
$router->post('api/admin/members/{id}/delete',  'AdminController', 'destroy');
