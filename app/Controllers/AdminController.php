<?php

namespace Controllers;

use Core\Controller;
use Core\Mailer;
use Models\AdminLoginLog;
use Models\AdminUser;
use Models\Member;
use Models\MemberStore;
use Models\SystemSetting;

class AdminController extends Controller
{
    private Member $member;
    private MemberStore $memberStore;
    private AdminUser $adminUser;
    private AdminLoginLog $adminLoginLog;
    private SystemSetting $settings;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->member = new Member();
        $this->memberStore = new MemberStore();
        $this->adminUser = new AdminUser();
        $this->adminLoginLog = new AdminLoginLog();
        $this->settings = new SystemSetting();
    }

    /** GET /admin/login — 顯示登入頁 */
    public function loginPage(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->baseUrl('/admin'));
        }
        $this->render('admin.login', [
            'title' => '管理員登入',
            'adminTimeoutMinutes' => (int) floor($this->adminSessionTimeoutSeconds() / 60),
        ]);
    }

    /** GET /admin/setup-password/{token} */
    public function setupPasswordPage(string $token): void
    {
        $admin = $this->adminUser->findByPasswordSetupToken($token);
        $this->render('admin.setup-password', [
            'title' => '設定管理後台密碼',
            'token' => $token,
            'admin' => $admin ?: null,
            'valid' => $this->isValidPasswordSetupToken($admin),
        ]);
    }

    /** GET /admin — 顯示後台頁 */
    public function index(): void
    {
        $this->requireLogin();
        $this->render('admin.index', [
            'title' => '後台管理',
            'adminTimeoutSeconds' => $this->adminSessionTimeoutSeconds(),
        ]);
    }

    /** GET /admin/staff — 管理內部人員 */
    public function staffPage(): void
    {
        $this->requireLogin();
        if (($_SESSION['admin']['role'] ?? '') !== 'super_admin') {
            $this->redirect($this->baseUrl('/admin'));
        }
        $this->render('admin.staff', [
            'title' => '管理人員',
            'adminTimeoutSeconds' => $this->adminSessionTimeoutSeconds(),
            'adminTimeoutMinutes' => (int) floor($this->adminSessionTimeoutSeconds() / 60),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /** GET /admin/staff/{id}/edit — 單頁編輯內部管理人員 */
    public function staffEditPage(string $id): void
    {
        $this->requireLogin();
        if (($_SESSION['admin']['role'] ?? '') !== 'super_admin') {
            $this->redirect($this->baseUrl('/admin'));
        }

        $staff = $this->adminUser->find((int) $id);
        if ($staff) {
            unset(
                $staff['password'],
                $staff['active_session_id'],
                $staff['active_session_last_seen_at'],
                $staff['duplicate_login_attempt_at'],
                $staff['duplicate_login_attempt_ip'],
                $staff['password_setup_token'],
                $staff['password_setup_expires_at']
            );
        }

        $this->render('admin.staff-edit', [
            'title' => '編輯管理人員',
            'staff' => $staff ?: null,
            'adminTimeoutSeconds' => $this->adminSessionTimeoutSeconds(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /** GET /admin/members/{id}/edit — 單頁編輯會員 */
    public function edit(string $id): void
    {
        $this->requireLogin();

        $member = $this->member->find((int) $id);
        if (!$member) {
            http_response_code(404);
            $this->render('admin.edit', [
                'title' => '找不到會員',
                'member' => null,
            ]);
            return;
        }

        unset($member['password'], $member['email_verified_token']);
        $member['birth_date_roc'] = $this->formatRocDate($member['birth_date'] ?? null);
        $member['id_issue_date_roc'] = $this->formatRocDate($member['id_issue_date'] ?? null);
        $this->render('admin.edit', [
            'title' => '編輯會員',
            'member' => $member,
            'stores' => $this->memberStore->findByMember((int) $member['id']),
            'adminTimeoutSeconds' => $this->adminSessionTimeoutSeconds(),
        ]);
    }

    /** POST /api/admin/login — 管理員登入 */
    public function login(): void
    {
        $data = $this->input();
        $email = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        $admin = $this->adminUser->findByEmail($email);
        if (!$admin) {
            $this->json(['message' => '帳號或密碼錯誤。'], 422);
            return;
        }

        if (($admin['status'] ?? '') === 'pending_activation') {
            $this->json(['message' => '此管理人員帳號尚未完成信箱認證與密碼設定。'], 403);
            return;
        }

        if (($admin['status'] ?? '') !== 'active') {
            $this->json(['message' => '此管理人員帳號已停用。'], 403);
            return;
        }

        if (!password_verify($password, (string) $admin['password'])) {
            $this->json(['message' => '帳號或密碼錯誤。'], 422);
            return;
        }

        $clientIp = $this->clientIp();
        if (!$this->isAdminLoginIpAllowed($clientIp, (string) ($admin['allowed_ips'] ?? ''))) {
            $this->json(['message' => "目前 IP {$clientIp} 不在後台可登入 IP 清單內。"], 403);
            return;
        }

        if ($this->hasActiveAdminSession($admin)) {
            $this->adminUser->recordDuplicateLoginAttempt((int) $admin['id'], $clientIp);
            $this->json(['message' => '此管理帳號目前已在其他裝置或瀏覽器登入，系統已阻止第二個相同身分同時登入。'], 409);
            return;
        }

        session_regenerate_id(true);
        $activeSessionId = session_id();

        $loginLogId = $this->adminLoginLog->insert([
            'admin_user_id' => (int) $admin['id'],
            'ip_address' => $clientIp,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'login_at' => date('Y-m-d H:i:s'),
        ]);
        $this->adminUser->update((int) $admin['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->adminUser->setActiveSession((int) $admin['id'], $activeSessionId);

        $_SESSION['admin'] = [
            'id' => (int) $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'permission_group' => $admin['permission_group'] ?? null,
            'login_log_id' => $loginLogId,
            'logged_in_at' => date('Y-m-d H:i:s'),
            'last_activity_at' => time(),
        ];

        $this->json(['message' => '登入成功。']);
    }

    /** POST /api/admin/setup-password/{token} */
    public function setupPassword(string $token): void
    {
        $admin = $this->adminUser->findByPasswordSetupToken($token);
        if (!$this->isValidPasswordSetupToken($admin)) {
            $this->json(['message' => '設定密碼連結無效或已過期，請聯絡系統管理員重新建立帳號邀請。'], 404);
            return;
        }

        $data = $this->input();
        $password = (string) ($data['password'] ?? '');
        $confirm = (string) ($data['password_confirm'] ?? '');
        $errors = [];

        if (strlen($password) < 8) {
            $errors['password'] = '密碼至少需要 8 位字元。';
        }
        if ($password !== $confirm) {
            $errors['password_confirm'] = '兩次輸入的密碼不一致。';
        }
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $this->adminUser->update((int) $admin['id'], [
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'status' => 'active',
            'password_setup_token' => null,
            'password_setup_expires_at' => null,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message' => '密碼設定完成，管理人員帳號已正式啟用。',
            'login_url' => $this->baseUrl('/admin/login'),
        ]);
    }

    /** POST /api/admin/logout — 管理員登出 */
    public function logout(): void
    {
        $this->closeCurrentAdminLoginLog();
        $this->clearCurrentAdminSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->json(['message' => '已登出。']);
    }

    /** GET /api/admin/session-status — 後台 session 狀態與重複登入警告 */
    public function sessionStatus(): void
    {
        if (empty($_SESSION['admin']['id'])) {
            $this->json(['message' => '請先登入管理後台。'], 401);
            return;
        }

        $timeout = $this->adminSessionTimeoutSeconds();
        $lastActivity = (int) ($_SESSION['admin']['last_activity_at'] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) >= $timeout) {
            $this->closeCurrentAdminLoginLog();
            $this->clearCurrentAdminSession();
            unset($_SESSION['admin']);
            $this->json(['message' => '後台登入已逾時。'], 401);
            return;
        }

        $admin = $this->adminUser->find((int) $_SESSION['admin']['id']);
        if (!$admin) {
            unset($_SESSION['admin']);
            $this->json(['message' => '此後台帳號已在其他地方登入，請重新登入。'], 409);
            return;
        }

        if (empty($admin['active_session_id'])) {
            $this->adminUser->setActiveSession((int) $admin['id'], session_id());
            $admin['active_session_id'] = session_id();
        }

        if (($admin['active_session_id'] ?? '') !== session_id()) {
            unset($_SESSION['admin']);
            $this->json(['message' => '此後台帳號已在其他地方登入，請重新登入。'], 409);
            return;
        }

        $warning = $this->adminUser->consumeDuplicateLoginAttempt((int) $admin['id'], session_id());
        $this->json([
            'remaining_seconds' => max(0, ($lastActivity + $timeout) - time()),
            'duplicate_login_attempt' => $warning,
        ]);
    }

    /** POST /api/admin/session-touch — 使用者操作後刷新後台閒置倒數 */
    public function sessionTouch(): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }

        $timeout = $this->adminSessionTimeoutSeconds();
        $lastActivity = (int) ($_SESSION['admin']['last_activity_at'] ?? time());
        $this->json([
            'remaining_seconds' => max(0, ($lastActivity + $timeout) - time()),
        ]);
    }

    /** GET /api/admin/members — 會員列表（支援篩選 & 搜尋） */
    public function list(): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }
        $keyword = trim($_GET['q']      ?? '');
        $type    = trim($_GET['type']   ?? '');
        $status  = trim($_GET['status'] ?? '');

        $members = $this->member->search($keyword, $type, $status);
        $storeCounts = $this->memberStore->countsByMemberIds(array_column($members, 'id'));

        // 隱藏敏感欄位
        $members = array_map(function ($m) use ($storeCounts) {
            unset($m['password'], $m['email_verified_token']);
            $m['store_count'] = $storeCounts[(int) $m['id']] ?? 0;
            return $m;
        }, $members);

        $this->json($members);
    }

    /** GET /api/admin/stats — 統計數字 */
    public function stats(): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }
        $this->json($this->member->stats());
    }

    /** GET /api/admin/staff — 管理人員列表 */
    public function staffList(): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $staff = array_map(function ($row) {
            unset(
                $row['password'],
                $row['active_session_id'],
                $row['active_session_last_seen_at'],
                $row['duplicate_login_attempt_at'],
                $row['duplicate_login_attempt_ip'],
                $row['password_setup_token'],
                $row['password_setup_expires_at']
            );
            return $row;
        }, $this->adminUser->all('created_at DESC'));

        $this->json($staff);
    }

    /** POST /api/admin/staff/create — 新增管理人員 */
    public function staffCreate(): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $data = $this->input();
        $errors = $this->validateStaff($data, false);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $setupUrl = $this->absoluteUrl('/admin/setup-password/' . $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        $id = $this->adminUser->insert([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            'role' => $this->roleForPermissionGroup((string) ($data['permission_group'] ?? '')),
            'permission_group' => trim((string) ($data['permission_group'] ?? '')) ?: null,
            'status' => 'pending_activation',
            'allowed_ips' => $this->normalizeAllowedIps((string) ($data['allowed_ips'] ?? '')),
            'password_setup_token' => $token,
            'password_setup_expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->sendAdminPasswordSetupEmail(trim($data['email']), trim($data['name']), $setupUrl);

        $this->json([
            'message' => '管理人員已新增，系統已寄出信箱認證與設定密碼連結。',
            'id' => $id,
            'setup_url' => defined('APP_ENV') && APP_ENV === 'development' ? $setupUrl : null,
        ], 201);
    }

    /** POST /api/admin/staff/{id}/update — 更新管理人員 */
    public function staffUpdate(string $id): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $staffId = (int) $id;
        $existing = $this->adminUser->find($staffId);
        if (!$existing) {
            $this->json(['message' => '找不到管理人員。'], 404);
            return;
        }

        $data = $this->input();
        $errors = $this->validateStaff($data, false, $staffId);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $payload = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'role' => $this->roleForPermissionGroup((string) ($data['permission_group'] ?? '')),
            'permission_group' => trim((string) ($data['permission_group'] ?? '')) ?: null,
            'status' => in_array($data['status'] ?? '', ['pending_activation', 'active', 'suspended'], true) ? $data['status'] : 'active',
            'allowed_ips' => $this->normalizeAllowedIps((string) ($data['allowed_ips'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['password'])) {
            $payload['password'] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }

        if ($staffId === (int) ($_SESSION['admin']['id'] ?? 0) && ($payload['status'] ?? '') === 'suspended') {
            $this->json(['message' => '不能停用目前登入中的自己。'], 422);
            return;
        }

        $this->adminUser->update($staffId, $payload);
        $this->json(['message' => '管理人員已更新。']);
    }

    /** POST /api/admin/staff/{id}/delete — 刪除管理人員 */
    public function staffDelete(string $id): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $staffId = (int) $id;
        if ($staffId === (int) ($_SESSION['admin']['id'] ?? 0)) {
            $this->json(['message' => '不能刪除目前登入中的自己。'], 422);
            return;
        }
        if (!$this->adminUser->find($staffId)) {
            $this->json(['message' => '找不到管理人員。'], 404);
            return;
        }

        $this->adminUser->delete($staffId);
        $this->json(['message' => '管理人員已刪除。']);
    }

    /** GET /api/admin/staff/{id}/login-logs — 管理人員登入紀錄 */
    public function staffLoginLogs(string $id): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $staffId = (int) $id;
        if (!$this->adminUser->find($staffId)) {
            $this->json(['message' => '找不到管理人員。'], 404);
            return;
        }

        $this->json($this->adminLoginLog->latestByAdminUser($staffId, 50));
    }

    /** GET /api/admin/settings/security — 系統安全設定 */
    public function securitySettings(): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $this->json([
            'admin_session_timeout_minutes' => (int) floor($this->adminSessionTimeoutSeconds() / 60),
            'admin_allowed_ips' => $this->globalAdminAllowedIpItems(),
            'admin_permission_groups' => $this->permissionGroups(),
        ]);
    }

    /** POST /api/admin/settings/security — 更新系統安全設定 */
    public function updateSecuritySettings(): void
    {
        if (!$this->requireSuperAdmin()) {
            return;
        }

        $data = $this->input();
        $minutes = (int) ($data['admin_session_timeout_minutes'] ?? 0);
        if ($minutes < 1 || $minutes > 1440) {
            $this->json(['message' => '自動登出時間需介於 1 到 1440 分鐘。'], 422);
            return;
        }
        $adminAllowedIpItems = $this->normalizeAllowedIpItems($data['admin_allowed_ips'] ?? '');
        if ($adminAllowedIpItems === false) {
            $this->json(['message' => '可登入 IP 格式不正確，請使用單一 IP 或 CIDR，例如 127.0.0.1、::1、192.168.1.0/24'], 422);
            return;
        }
        $permissionGroups = $this->normalizePermissionGroups($data['admin_permission_groups'] ?? []);
        if ($permissionGroups === false) {
            $this->json(['message' => '群組資料格式不正確，請確認群組名稱與權限設定。'], 422);
            return;
        }

        $this->settings->set('admin_session_timeout_seconds', (string) ($minutes * 60));
        $this->settings->set('admin_allowed_ips', json_encode($adminAllowedIpItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->settings->set('admin_permission_groups', json_encode($permissionGroups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->json(['message' => '安全設定已更新。']);
    }

    /** GET /api/admin/members/{id}/id-documents/{side} — 查看身分證電子檔 */
    public function idDocument(string $id, string $side): void
    {
        $this->requireLogin();

        $member = $this->member->find((int) $id);
        if (!$member || !in_array($side, ['front', 'back'], true)) {
            http_response_code(404);
            echo '找不到檔案。';
            return;
        }

        $field = $side === 'front' ? 'id_card_front_path' : 'id_card_back_path';
        $relativePath = $member[$field] ?? '';
        $baseDir = realpath(BASE_PATH . '/storage/id-documents');
        $filePath = realpath(BASE_PATH . '/' . $relativePath);

        if (!$relativePath || !$baseDir || !$filePath || !str_starts_with($filePath, $baseDir) || !is_file($filePath)) {
            http_response_code(404);
            echo '找不到檔案。';
            return;
        }

        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    }

    /** POST /api/admin/members/{id}/update — 更新會員 */
    public function update(string $id): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }

        $memberId = (int) $id;
        $existing = $this->member->find($memberId);
        if (!$existing) {
            $this->json(['message' => '找不到會員。'], 404);
            return;
        }

        $data = $this->input();
        $canEditCompanyProfile = (($_SESSION['admin']['role'] ?? '') === 'super_admin');
        if (!$canEditCompanyProfile && ($existing['type'] ?? '') === 'company') {
            $data['type'] = 'company';
            $data['tax_id'] = $existing['tax_id'] ?? '';
            $data['company_name'] = $existing['company_name'] ?? '';
            $data['website'] = $existing['website'] ?? '';
            $data['industry'] = $existing['industry'] ?? '';
            $data['is_dealer'] = !empty($existing['is_dealer']) ? 1 : 0;
        }
        $errors = $this->validateMember($data, $memberId);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $birthDate = $this->parseRocDate($data['birth_date'] ?? '');
        $issueDate = $this->parseRocDate($data['id_issue_date'] ?? '');
        $mobilePhone = $this->normalizeTaiwanMobile($data['mobile_phone'] ?? '');

        $type = $data['type'];
        $payload = [
            'type' => $type,
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone_area_code' => trim($data['phone_area_code'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'mobile_phone' => $mobilePhone,
            'contact_address' => trim($data['contact_address'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($data['password'])) {
            $payload['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if ($type === 'personal') {
            $payload += [
                'id_number' => strtoupper(trim($data['id_number'])),
                'line_id' => trim($data['line_id'] ?? ''),
                'id_issue_date' => $issueDate,
                'id_issue_place' => trim($data['id_issue_place'] ?? ''),
                'id_issue_type' => $data['id_issue_type'] ?: null,
                'birth_date' => $birthDate,
                'gender' => $data['gender'] ?: null,
                'tax_id' => null,
                'company_name' => null,
                'website' => null,
                'industry' => null,
                'is_dealer' => 0,
            ];
        } else {
            $payload += [
                'id_number' => null,
                'line_id' => null,
                'birth_date' => null,
                'gender' => null,
                'tax_id' => trim($data['tax_id']),
                'company_name' => trim($data['company_name']),
                'website' => trim($data['website'] ?? ''),
                'industry' => $data['industry'] ?: null,
                'is_dealer' => !empty($data['is_dealer']) ? 1 : 0,
            ];
        }

        $this->member->update($memberId, $payload);
        $this->json(['message' => '會員資料已更新。']);
    }

    /** POST /api/admin/members/{memberId}/stores/{storeId}/update — 更新會員商店 */
    public function updateMemberStore(string $memberId, string $storeId): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }

        $member = $this->member->find((int) $memberId);
        $store = $this->memberStore->findByMemberAndId((int) $memberId, (int) $storeId);
        if (!$member || !$store) {
            $this->json(['message' => '找不到商店資料。'], 404);
            return;
        }

        $data = $this->input();
        $errors = [];
        $status = $data['status'] ?? 'pending';
        $storeType = $data['store_type'] ?? 'online';

        if (!in_array($status, ['pending', 'active', 'suspended', 'rejected'], true)) {
            $errors['status'] = '請選擇有效的商店狀態。';
        }
        if (!in_array($storeType, ['online', 'physical'], true)) {
            $errors['store_type'] = '請選擇有效的商店類型。';
        }
        foreach (['store_name' => '商店名稱', 'store_email' => '商店電子信箱', 'contact_name' => '聯絡人名稱'] as $field => $label) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = "{$label}為必填。";
            }
        }
        if (!empty($data['store_email']) && !filter_var($data['store_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['store_email'] = '商店電子信箱格式不正確。';
        }
        if (!empty($data['store_url']) && !filter_var($data['store_url'], FILTER_VALIDATE_URL)) {
            $errors['store_url'] = '商店網址格式不正確。';
        }
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $paymentTools = $data['payment_tools'] ?? [];
        if (!is_array($paymentTools)) {
            $paymentTools = [];
        }

        $this->memberStore->update((int) $storeId, [
            'status' => $status,
            'store_type' => $storeType,
            'store_name' => trim((string) $data['store_name']),
            'store_email' => trim((string) $data['store_email']),
            'foreign_statement_name' => trim((string) ($data['foreign_statement_name'] ?? '')),
            'store_phone' => trim((string) ($data['store_phone'] ?? '')),
            'store_fax' => trim((string) ($data['store_fax'] ?? '')),
            'store_city' => trim((string) ($data['store_city'] ?? '')),
            'store_district' => trim((string) ($data['store_district'] ?? '')),
            'store_address' => trim((string) ($data['store_address'] ?? '')),
            'contact_name' => trim((string) $data['contact_name']),
            'contact_mobile' => trim((string) ($data['contact_mobile'] ?? '')),
            'contact_phone' => trim((string) ($data['contact_phone'] ?? '')),
            'industry' => trim((string) ($data['industry'] ?? '')),
            'product_type' => trim((string) ($data['product_type'] ?? '')),
            'guarantee_type' => trim((string) ($data['guarantee_type'] ?? '')),
            'delivery_period' => (int) ($data['delivery_period'] ?? 0),
            'delivery_unit' => trim((string) ($data['delivery_unit'] ?? '個月')),
            'guarantee_note_type' => trim((string) ($data['guarantee_note_type'] ?? 'not_required')),
            'guarantee_note' => trim((string) ($data['guarantee_note'] ?? '')),
            'average_order_amount' => trim((string) ($data['average_order_amount'] ?? '')),
            'store_url_type' => trim((string) ($data['store_url_type'] ?? 'url')),
            'store_url' => trim((string) ($data['store_url'] ?? '')),
            'store_description' => trim((string) ($data['store_description'] ?? '')),
            'payment_tools' => json_encode(array_values($paymentTools), JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => '商店資料已更新。']);
    }

    /** POST /api/admin/members/{id}/resend-verification — 重寄會員信箱驗證與密碼設定信 */
    public function resendMemberVerification(string $id): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }

        $member = $this->member->find((int) $id);
        if (!$member) {
            $this->json(['message' => '找不到會員。'], 404);
            return;
        }

        if (!empty($member['email_verified_at'])) {
            $this->json(['message' => '此會員已完成信箱驗證，不需要重新發送。'], 422);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->member->update((int) $member['id'], [
            'status' => 'email_unverified',
            'email_verified_token' => $token,
            'email_verified_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $setupUrl = $this->absoluteUrl("/verify/{$token}");
        $sent = $this->sendMemberPasswordSetupEmail((string) $member['email'], (string) $member['name'], $setupUrl);
        if (!$sent) {
            $this->json(['message' => '驗證信寄送失敗，請確認主機郵件設定。'], 500);
            return;
        }

        $this->json([
            'message' => '已重新發送信箱驗證與密碼設定信。',
            'verification_url' => APP_ENV === 'development' ? $setupUrl : null,
        ]);
    }

    /** POST /api/admin/members/{id}/approve — 審核通過 */
    public function approve(string $id): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }
        $member = $this->member->find((int) $id);
        if (!$member) {
            $this->json(['message' => '找不到會員。'], 404);
            return;
        }
        if (($member['status'] ?? '') === 'email_unverified' || empty($member['email_verified_at'])) {
            $this->json(['message' => '會員尚未完成信箱驗證與密碼設定，不能審核通過。'], 422);
            return;
        }
        $this->member->update((int) $id, [
            'status'     => 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['message' => '已審核通過。']);
    }

    /** POST /api/admin/members/{id}/suspend — 停用帳號 */
    public function suspend(string $id): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }
        if (!$this->member->find((int) $id)) {
            $this->json(['message' => '找不到會員。'], 404);
            return;
        }
        $this->member->update((int) $id, [
            'status'     => 'suspended',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['message' => '帳號已停用。']);
    }

    /** POST /api/admin/members/{id}/delete — 刪除 */
    public function destroy(string $id): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }
        if (!$this->member->find((int) $id)) {
            $this->json(['message' => '找不到會員。'], 404);
            return;
        }
        $this->member->delete((int) $id);
        $this->json(['message' => '已刪除。']);
    }

    private function isLoggedIn(): bool
    {
        if (empty($_SESSION['admin']['email'])) {
            return false;
        }

        $timeout = $this->adminSessionTimeoutSeconds();
        $lastActivity = (int) ($_SESSION['admin']['last_activity_at'] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) >= $timeout) {
            $this->closeCurrentAdminLoginLog();
            $this->clearCurrentAdminSession();
            unset($_SESSION['admin']);
            return false;
        }

        $_SESSION['admin']['last_activity_at'] = time();
        $admin = $this->adminUser->find((int) $_SESSION['admin']['id']);
        if ($admin && empty($admin['active_session_id'])) {
            $this->adminUser->setActiveSession((int) $_SESSION['admin']['id'], session_id());
        } elseif ($admin && ($admin['active_session_id'] ?? '') === session_id()) {
            $this->adminUser->touchActiveSession((int) $_SESSION['admin']['id'], session_id());
        } else {
            unset($_SESSION['admin']);
            return false;
        }
        return true;
    }

    private function adminSessionTimeoutSeconds(): int
    {
        $value = (int) $this->settings->get('admin_session_timeout_seconds', (string) ADMIN_SESSION_TIMEOUT_SECONDS);
        return max(60, min(86400, $value));
    }

    private function closeCurrentAdminLoginLog(): void
    {
        $logId = (int) ($_SESSION['admin']['login_log_id'] ?? 0);
        $adminUserId = (int) ($_SESSION['admin']['id'] ?? 0);
        if ($logId > 0 && $adminUserId > 0) {
            $this->adminLoginLog->closeSessionLog($logId, $adminUserId);
        } elseif ($adminUserId > 0) {
            $this->adminLoginLog->closeLatestOpenLogByAdminUser($adminUserId);
        }
    }

    private function clearCurrentAdminSession(): void
    {
        $adminUserId = (int) ($_SESSION['admin']['id'] ?? 0);
        if ($adminUserId > 0) {
            $this->adminUser->clearActiveSession($adminUserId, session_id());
        }
    }

    private function hasActiveAdminSession(array $admin): bool
    {
        $adminId = (int) ($admin['id'] ?? 0);
        $activeSessionId = (string) ($admin['active_session_id'] ?? '');
        if ($activeSessionId === '') {
            return false;
        }

        $lastSeenAt = strtotime((string) ($admin['active_session_last_seen_at'] ?? '')) ?: 0;
        if ($lastSeenAt <= 0 || (time() - $lastSeenAt) > $this->adminSessionTimeoutSeconds()) {
            if ($adminId > 0) {
                $this->adminUser->clearAnyActiveSession($adminId);
            }
            return false;
        }

        if (!$this->adminSessionFileExists($activeSessionId)) {
            if ($adminId > 0) {
                $this->adminUser->clearAnyActiveSession($adminId);
            }
            return false;
        }

        return true;
    }

    private function adminSessionFileExists(string $sessionId): bool
    {
        if (!preg_match('/^[a-zA-Z0-9,-]{1,128}$/', $sessionId)) {
            return false;
        }

        $savePath = session_save_path();
        if ($savePath === '') {
            $savePath = sys_get_temp_dir();
        }

        if (str_contains($savePath, ';')) {
            $parts = explode(';', $savePath);
            $savePath = end($parts) ?: sys_get_temp_dir();
        }

        return is_file(rtrim($savePath, "\\/") . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
    }

    private function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect($this->baseUrl('/admin/login'));
        }
    }

    private function requireApiLogin(): bool
    {
        if ($this->isLoggedIn()) {
            return true;
        }

        $this->json(['message' => '請先登入管理後台。'], 401);
        return false;
    }

    private function requireSuperAdmin(): bool
    {
        if (!$this->requireApiLogin()) {
            return false;
        }
        if (($_SESSION['admin']['role'] ?? '') === 'super_admin') {
            return true;
        }

        $this->json(['message' => '只有系統管理員可以管理內部人員。'], 403);
        return false;
    }

    private function baseUrl(string $path = ''): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        return $base . $path;
    }

    private function absoluteUrl(string $path = ''): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $this->baseUrl($path);
    }

    private function isValidPasswordSetupToken(array|false $admin): bool
    {
        if (!$admin || empty($admin['password_setup_token'])) {
            return false;
        }
        if (($admin['status'] ?? '') !== 'pending_activation') {
            return false;
        }

        $expiresAt = strtotime((string) ($admin['password_setup_expires_at'] ?? '')) ?: 0;
        return $expiresAt > time();
    }

    private function sendAdminPasswordSetupEmail(string $email, string $name, string $setupUrl): void
    {
        $subject = '管理後台帳號啟用通知';
        $body = "{$name} 您好：\n\n請點擊以下連結完成信箱認證並設定管理後台密碼：\n{$setupUrl}\n\n此連結 24 小時內有效。若您未申請此帳號，請忽略本信。";
        Mailer::send($email, $subject, $body);
    }

    private function sendMemberPasswordSetupEmail(string $email, string $name, string $setupUrl): bool
    {
        $body = implode("\r\n", [
            "{$name} 您好：",
            '',
            '請點擊以下連結完成信箱驗證並設定會員登入密碼：',
            $setupUrl,
            '',
            '完成設定後，會員資料會進入後台待審核狀態。',
            '',
            '若您沒有申請會員註冊，請忽略此信。',
        ]);
        return Mailer::send($email, '會員信箱驗證與密碼設定', $body);
    }

    private function validateMember(array $data, int $memberId): array
    {
        $errors = [];
        $type = $data['type'] ?? '';
        $email = trim($data['email'] ?? '');

        if (!in_array($type, ['personal', 'company'], true)) {
            $errors['type'] = '請選擇會員類型';
        }
        if (trim($data['name'] ?? '') === '') {
            $errors['name'] = '請輸入姓名';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '請輸入有效的電子郵件';
        }
        if (!empty($data['password']) && strlen((string) $data['password']) < 8) {
            $errors['password'] = '新密碼至少需要 8 位字元';
        }
        if (!$this->isValidTaiwanMobile($data['mobile_phone'] ?? '')) {
            $errors['mobile_phone'] = '請輸入有效的台灣手機號碼';
        }
        if (trim($data['contact_address'] ?? '') === '') {
            $errors['contact_address'] = '請輸入聯絡地址';
        }

        if ($type === 'personal') {
            $idno = strtoupper(trim($data['id_number'] ?? ''));
            if (!$this->isValidTaiwanIdNumber($idno)) {
                $errors['id_number'] = '請輸入有效的身分證號（含檢核碼）';
            } elseif (empty($errors['email']) && $this->member->personalIdentityExists($email, $idno, $memberId)) {
                $errors['id_number'] = '此電子郵件與身分證號組合已被其他會員使用';
            }
            if (!$this->parseRocDate($data['id_issue_date'] ?? '')) {
                $errors['id_issue_date'] = '請輸入有效的民國發證日期，例如 113/01/02';
            }
            if (!$this->parseRocDate($data['birth_date'] ?? '')) {
                $errors['birth_date'] = '請輸入有效的民國出生日期，例如 083/05/15';
            }
            if (trim($data['id_issue_place'] ?? '') === '') {
                $errors['id_issue_place'] = '請選擇身分證發證地點';
            }
            if (!in_array($data['id_issue_type'] ?? '', ['first', 'replace', 'renew'], true)) {
                $errors['id_issue_type'] = '請選擇身分證補領換類別';
            }
        } elseif ($type === 'company') {
            $taxId = trim($data['tax_id'] ?? '');
            if (!preg_match('/^\d{8}$/', $taxId)) {
                $errors['tax_id'] = '統一編號需為 8 碼數字';
            } elseif (empty($errors['email']) && $this->member->companyIdentityExists($email, $taxId, $memberId)) {
                $errors['tax_id'] = '此電子郵件與統一編號組合已被其他會員使用';
            }
            if (trim($data['company_name'] ?? '') === '') {
                $errors['company_name'] = '請輸入公司名稱';
            }
        }

        return $errors;
    }

    private function validateStaff(array $data, bool $passwordRequired, ?int $staffId = null): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($name === '') {
            $errors['name'] = '請輸入管理人員姓名';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '請輸入有效的管理人員帳號';
        } elseif ($this->adminUser->emailExists($email, $staffId)) {
            $errors['email'] = '此管理人員帳號已存在';
        }
        if (!$this->isValidPermissionGroup((string) ($data['permission_group'] ?? ''))) {
            $errors['permission_group'] = '請選擇群組管理內建立的權限群組';
        }
        if (!$passwordRequired && array_key_exists('status', $data) && !in_array($data['status'] ?? '', ['pending_activation', 'active', 'suspended'], true)) {
            $errors['status'] = '請選擇帳號狀態';
        }
        if ($passwordRequired && strlen($password) < 8) {
            $errors['password'] = '密碼至少需要 8 位字元';
        } elseif (!$passwordRequired && $password !== '' && strlen($password) < 8) {
            $errors['password'] = '新密碼至少需要 8 位字元';
        }
        if (!$this->validateAllowedIps((string) ($data['allowed_ips'] ?? ''))) {
            $errors['allowed_ips'] = '允許登入 IP 格式不正確，請使用單一 IP 或 CIDR，例如 127.0.0.1、192.168.1.0/24';
        }

        return $errors;
    }

    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function normalizeAllowedIps(string $value): ?string
    {
        $entries = $this->allowedIpEntries($value);
        return $entries ? implode("\n", $entries) : null;
    }

    private function normalizeAllowedIpsFromMixed(mixed $value): string|false
    {
        $entries = is_array($value)
            ? $this->allowedIpEntries(implode("\n", array_map('strval', $value)))
            : $this->allowedIpEntries((string) $value);

        foreach ($entries as $entry) {
            if (!$this->isValidIpRule($entry)) {
                return false;
            }
        }

        return implode("\n", $entries);
    }

    private function normalizeAllowedIpItems(mixed $value): array|false
    {
        $rawItems = is_array($value) ? $value : $this->allowedIpEntries((string) $value);
        $items = [];
        $seen = [];

        foreach ($rawItems as $item) {
            $ip = is_array($item) ? trim((string) ($item['ip'] ?? '')) : trim((string) $item);
            $note = is_array($item) ? trim((string) ($item['note'] ?? '')) : '';
            if ($ip === '') {
                continue;
            }
            if (!$this->isValidIpRule($ip)) {
                return false;
            }
            if (isset($seen[$ip])) {
                continue;
            }
            $seen[$ip] = true;
            $items[] = [
                'ip' => $ip,
                'note' => function_exists('mb_substr') ? mb_substr($note, 0, 100) : substr($note, 0, 100),
            ];
        }

        return $items;
    }

    private function allowedIpEntries(string $value): array
    {
        $parts = preg_split('/[\r\n,;]+/', $value) ?: [];
        $entries = [];
        foreach ($parts as $part) {
            $entry = trim($part);
            if ($entry !== '') {
                $entries[] = $entry;
            }
        }
        return array_values(array_unique($entries));
    }

    private function validateAllowedIps(string $value): bool
    {
        foreach ($this->allowedIpEntries($value) as $entry) {
            if (!$this->isValidIpRule($entry)) {
                return false;
            }
        }
        return true;
    }

    private function isValidIpRule(string $rule): bool
    {
        if (!str_contains($rule, '/')) {
            return filter_var($rule, FILTER_VALIDATE_IP) !== false;
        }

        [$ip, $prefix] = explode('/', $rule, 2);
        if (!ctype_digit($prefix) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $maxPrefix = str_contains($ip, ':') ? 128 : 32;
        $prefixLength = (int) $prefix;
        return $prefixLength >= 0 && $prefixLength <= $maxPrefix;
    }

    private function isIpAllowed(string $clientIp, string $allowedIps): bool
    {
        $entries = $this->allowedIpEntries($allowedIps);
        if (!$entries) {
            return true;
        }

        foreach ($entries as $entry) {
            if ($this->ipMatchesRule($clientIp, $entry)) {
                return true;
            }
        }
        return false;
    }

    private function isAdminLoginIpAllowed(string $clientIp, string $staffAllowedIps): bool
    {
        return $this->ipMatchesAnyRule($clientIp, $this->globalAdminAllowedIps())
            || $this->ipMatchesAnyRule($clientIp, $staffAllowedIps);
    }

    private function globalAdminAllowedIps(): string
    {
        return implode("\n", array_column($this->globalAdminAllowedIpItems(), 'ip'));
    }

    private function globalAdminAllowedIpItems(): array
    {
        $value = (string) $this->settings->get('admin_allowed_ips', "127.0.0.1\n::1");
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $items = $this->normalizeAllowedIpItems($decoded);
            if ($items !== false) {
                return $items;
            }
        }

        $items = $this->normalizeAllowedIpItems($value);
        return $items !== false ? $items : [];
    }

    private function permissionGroups(): array
    {
        $value = (string) $this->settings->get('admin_permission_groups', '[]');
        $decoded = json_decode($value, true);
        $groups = $this->normalizePermissionGroups(is_array($decoded) ? $decoded : []);
        return $groups !== false ? $groups : [];
    }

    private function isValidPermissionGroup(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }
        foreach ($this->permissionGroups() as $group) {
            if (($group['name'] ?? '') === $name) {
                return true;
            }
        }
        return false;
    }

    private function roleForPermissionGroup(string $name): string
    {
        $name = trim($name);
        foreach ($this->permissionGroups() as $group) {
            if (($group['name'] ?? '') !== $name) {
                continue;
            }
            $permissions = $group['permissions'] ?? [];
            return array_intersect($permissions, ['staff.manage', 'group.manage', 'security.ip'])
                ? 'super_admin'
                : 'staff';
        }
        return 'staff';
    }

    private function normalizePermissionGroups(mixed $value): array|false
    {
        if (!is_array($value)) {
            return false;
        }

        $allowedPermissions = [
            'member.view', 'member.edit', 'member.review', 'member.delete',
            'dealer.view', 'dealer.edit',
            'security.ip', 'staff.manage', 'group.manage',
        ];
        $allowedMap = array_fill_keys($allowedPermissions, true);
        $groups = [];
        $seen = [];

        foreach ($value as $item) {
            if (!is_array($item)) {
                return false;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (isset($seen[$name])) {
                continue;
            }
            $permissions = $item['permissions'] ?? [];
            if (!is_array($permissions)) {
                return false;
            }
            $normalizedPermissions = [];
            foreach ($permissions as $permission) {
                $permission = trim((string) $permission);
                if (isset($allowedMap[$permission])) {
                    $normalizedPermissions[] = $permission;
                }
            }
            $seen[$name] = true;
            $groups[] = [
                'name' => function_exists('mb_substr') ? mb_substr($name, 0, 60) : substr($name, 0, 60),
                'permissions' => array_values(array_unique($normalizedPermissions)),
            ];
        }

        return $groups;
    }

    private function ipMatchesAnyRule(string $clientIp, string $allowedIps): bool
    {
        foreach ($this->allowedIpEntries($allowedIps) as $entry) {
            if ($this->ipMatchesRule($clientIp, $entry)) {
                return true;
            }
        }
        return false;
    }

    private function ipMatchesRule(string $clientIp, string $rule): bool
    {
        if (!str_contains($rule, '/')) {
            return $clientIp === $rule;
        }

        [$network, $prefix] = explode('/', $rule, 2);
        $clientPacked = inet_pton($clientIp);
        $networkPacked = inet_pton($network);
        if ($clientPacked === false || $networkPacked === false || strlen($clientPacked) !== strlen($networkPacked)) {
            return false;
        }

        $prefixLength = (int) $prefix;
        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($clientPacked, 0, $fullBytes) !== substr($networkPacked, 0, $fullBytes)) {
            return false;
        }
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($clientPacked[$fullBytes]) & $mask) === (ord($networkPacked[$fullBytes]) & $mask);
    }

    private function isValidTaiwanIdNumber(string $idno): bool
    {
        $idno = strtoupper(trim($idno));
        if (!preg_match('/^[A-Z][12][0-9]{8}$/', $idno)) {
            return false;
        }

        $letterCodes = [
            'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
            'F' => 15, 'G' => 16, 'H' => 17, 'I' => 34, 'J' => 18,
            'K' => 19, 'L' => 20, 'M' => 21, 'N' => 22, 'O' => 35,
            'P' => 23, 'Q' => 24, 'R' => 25, 'S' => 26, 'T' => 27,
            'U' => 28, 'V' => 29, 'W' => 32, 'X' => 30, 'Y' => 31,
            'Z' => 33,
        ];

        $code = $letterCodes[$idno[0]];
        $sum = intdiv($code, 10) + ($code % 10) * 9;
        for ($i = 1; $i <= 8; $i++) {
            $sum += ((int) $idno[$i]) * (9 - $i);
        }
        $sum += (int) $idno[9];

        return $sum % 10 === 0;
    }

    private function normalizeTaiwanMobile(string $value): string
    {
        return preg_replace('/[\s\-\(\)]/', '', trim($value)) ?? '';
    }

    private function isValidTaiwanMobile(string $value): bool
    {
        return (bool) preg_match('/^09\d{8}$/', $this->normalizeTaiwanMobile($value));
    }

    private function parseRocDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^(\d{2,3})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1] + 1911;
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function formatRocDate(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $parts = explode('-', $value);
        if (count($parts) !== 3) {
            return '';
        }

        return sprintf('%03d/%02d/%02d', ((int) $parts[0]) - 1911, (int) $parts[1], (int) $parts[2]);
    }
}
