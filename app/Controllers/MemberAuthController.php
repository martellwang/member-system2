<?php

namespace Controllers;

use Core\Controller;
use Models\Member;
use Models\MemberStore;

class MemberAuthController extends Controller
{
    private Member $member;
    private MemberStore $memberStore;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->member = new Member();
        $this->memberStore = new MemberStore();
    }

    /** GET /login — 會員登入頁 */
    public function loginPage(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->baseUrl('/member'));
        }

        $this->render('member.login', [
            'title' => '會員登入',
            'hideNavbar' => true,
        ]);
    }

    /** POST /api/members/login — 會員登入 */
    public function login(): void
    {
        $data = $this->input();
        $email = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $memberType = (string) ($data['member_type'] ?? '');
        $idNumber = strtoupper(trim((string) ($data['id_number'] ?? '')));
        $taxId = preg_replace('/\D+/', '', (string) ($data['tax_id'] ?? ''));

        if (!in_array($memberType, ['personal', 'company'], true)) {
            $this->json(['message' => '請選擇登入身分。'], 422);
            return;
        }

        if ($email === '' || $password === '') {
            $this->json(['message' => '請輸入電子郵件與密碼。'], 422);
            return;
        }

        if ($memberType === 'personal' && !preg_match('/^[A-Z][12][0-9]{8}$/', $idNumber)) {
            $this->json(['message' => '個人會員登入請輸入身分證號。'], 422);
            return;
        }

        if ($memberType === 'company' && !preg_match('/^\d{8}$/', $taxId)) {
            $this->json(['message' => '公司法人登入請輸入 8 碼統一編號。'], 422);
            return;
        }

        $member = $memberType === 'personal'
            ? $this->member->findPersonalByIdentity($email, $idNumber)
            : $this->member->findCompanyByIdentity($email, $taxId);

        if (!$member) {
            $this->json(['message' => '帳號或密碼錯誤。'], 422);
            return;
        }

        if (($member['status'] ?? '') === 'email_unverified' || empty($member['email_verified_at'])) {
            $this->json(['message' => '請先至信箱完成驗證並設定密碼。'], 403);
            return;
        }

        if (!password_verify($password, (string) $member['password'])) {
            $this->json(['message' => '帳號或密碼錯誤。'], 422);
            return;
        }

        if (($member['status'] ?? '') === 'suspended') {
            $this->json(['message' => '帳號已停用，請聯絡管理員。'], 403);
            return;
        }

        if ($this->hasActiveMemberSession($member)) {
            $this->member->recordDuplicateLoginAttempt((int) $member['id'], $this->clientIp());
            $this->json(['message' => '此會員帳號目前已在其他裝置或瀏覽器登入，系統已阻止第二個相同身分同時登入。'], 409);
            return;
        }

        session_regenerate_id(true);
        $this->member->setActiveSession((int) $member['id'], session_id());
        $_SESSION['member'] = [
            'id' => (int) $member['id'],
            'email' => $member['email'],
            'name' => $member['name'],
            'logged_in_at' => date('Y-m-d H:i:s'),
            'last_activity_at' => time(),
        ];

        $this->json(['message' => '登入成功。']);
    }

    /** GET /member — 會員中心 */
    public function dashboard(): void
    {
        $this->requireLogin();

        $member = $this->member->find((int) $_SESSION['member']['id']);
        if (!$member) {
            unset($_SESSION['member']);
            $this->redirect($this->baseUrl('/login'));
        }

        $stores = $this->memberStore->findByMember((int) $_SESSION['member']['id']);

        unset($member['password'], $member['email_verified_token']);
        $this->render('member.dashboard', [
            'title' => '會員中心',
            'member' => $member,
            'stores' => $stores,
        ]);
    }

    /** GET /member/logout — 會員登出 */
    public function logout(): void
    {
        $this->clearCurrentMemberSession();
        unset($_SESSION['member']);
        $this->redirect($this->baseUrl('/login'));
    }

    /** POST /api/members/logout — 會員登出 API */
    public function logoutApi(): void
    {
        $this->clearCurrentMemberSession();
        unset($_SESSION['member']);
        $this->json(['message' => '已登出。']);
    }

    /** GET /api/members/session-status — 會員 session 狀態與重複登入警告 */
    public function sessionStatus(): void
    {
        if (empty($_SESSION['member']['id'])) {
            $this->json(['message' => '請先登入會員。'], 401);
            return;
        }

        $timeout = max(60, MEMBER_SESSION_TIMEOUT_SECONDS);
        $lastActivity = (int) ($_SESSION['member']['last_activity_at'] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) >= $timeout) {
            $this->clearCurrentMemberSession();
            unset($_SESSION['member']);
            $this->json(['message' => '會員登入已逾時。'], 401);
            return;
        }

        $member = $this->member->find((int) $_SESSION['member']['id']);
        if (!$member) {
            unset($_SESSION['member']);
            $this->json(['message' => '此會員帳號已不存在，請重新登入。'], 409);
            return;
        }

        if (empty($member['active_session_id'])) {
            $this->member->setActiveSession((int) $member['id'], session_id());
            $member['active_session_id'] = session_id();
        }

        if (($member['active_session_id'] ?? '') !== session_id()) {
            unset($_SESSION['member']);
            $this->json(['message' => '此會員帳號已在其他地方登入，請重新登入。'], 409);
            return;
        }

        $warning = $this->member->consumeDuplicateLoginAttempt((int) $member['id'], session_id());
        $this->json([
            'remaining_seconds' => max(0, ($lastActivity + $timeout) - time()),
            'duplicate_login_attempt' => $warning,
        ]);
    }

    /** POST /api/members/session-touch — 使用者操作後刷新會員閒置倒數 */
    public function sessionTouch(): void
    {
        if (!$this->isLoggedIn()) {
            $this->json(['message' => '請先登入會員。'], 401);
            return;
        }

        $timeout = max(60, MEMBER_SESSION_TIMEOUT_SECONDS);
        $lastActivity = (int) ($_SESSION['member']['last_activity_at'] ?? time());
        $this->json([
            'remaining_seconds' => max(0, ($lastActivity + $timeout) - time()),
        ]);
    }

    /** POST /api/members/stores — 新增商店申請 */
    public function storeCreate(): void
    {
        if (!$this->isLoggedIn()) {
            $this->json(['message' => '請先登入會員。'], 401);
            return;
        }

        $data = $this->input();
        $errors = $this->validateStore($data);
        if ($errors) {
            $this->json(['message' => '請確認新增商店欄位。', 'errors' => $errors], 422);
            return;
        }

        $id = $this->memberStore->insert([
            'member_id' => (int) $_SESSION['member']['id'],
            'status' => 'pending',
            'store_type' => $data['store_type'],
            'store_name' => trim((string) $data['store_name']),
            'store_email' => trim((string) $data['store_email']),
            'foreign_statement_name' => trim((string) $data['foreign_statement_name']),
            'store_phone' => trim((string) ($data['store_phone'] ?? '')),
            'store_fax' => trim((string) ($data['store_fax'] ?? '')),
            'store_city' => trim((string) $data['store_city']),
            'store_district' => trim((string) $data['store_district']),
            'store_address' => trim((string) $data['store_address']),
            'contact_name' => trim((string) $data['contact_name']),
            'contact_phone' => trim((string) ($data['contact_phone'] ?? '')),
            'contact_mobile' => trim((string) ($data['contact_mobile'] ?? '')),
            'industry' => trim((string) $data['industry']),
            'product_type' => trim((string) $data['product_type']),
            'delivery_ratios' => json_encode($this->deliveryRatios($data), JSON_UNESCAPED_UNICODE),
            'guarantee_type' => trim((string) $data['guarantee_type']),
            'delivery_period' => (int) ($data['delivery_period'] ?? 0),
            'delivery_unit' => in_array(($data['delivery_unit'] ?? ''), ['日', '個月'], true) ? $data['delivery_unit'] : '個月',
            'guarantee_note_type' => trim((string) ($data['guarantee_note_type'] ?? 'not_required')),
            'guarantee_note' => trim((string) ($data['guarantee_note'] ?? '')),
            'average_order_amount' => trim((string) $data['average_order_amount']),
            'store_url_type' => trim((string) ($data['store_url_type'] ?? 'url')),
            'store_url' => trim((string) ($data['store_url'] ?? '')),
            'store_description' => trim((string) $data['store_description']),
            'payment_tools' => json_encode($data['payment_tools'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message' => '新增商店申請已送出，請等待後台審核。',
            'id' => $id,
        ], 201);
    }

    private function isLoggedIn(): bool
    {
        if (empty($_SESSION['member']['id'])) {
            return false;
        }

        $timeout = max(60, MEMBER_SESSION_TIMEOUT_SECONDS);
        $lastActivity = (int) ($_SESSION['member']['last_activity_at'] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) >= $timeout) {
            $this->clearCurrentMemberSession();
            unset($_SESSION['member']);
            return false;
        }

        $_SESSION['member']['last_activity_at'] = time();
        $member = $this->member->find((int) $_SESSION['member']['id']);
        if ($member && empty($member['active_session_id'])) {
            $this->member->setActiveSession((int) $_SESSION['member']['id'], session_id());
        } elseif ($member && ($member['active_session_id'] ?? '') === session_id()) {
            $this->member->touchActiveSession((int) $_SESSION['member']['id'], session_id());
        } else {
            unset($_SESSION['member']);
            return false;
        }
        return true;
    }

    private function validateStore(array $data): array
    {
        $errors = [];
        $required = [
            'store_name' => '請輸入商店名稱',
            'store_email' => '請輸入商店電子信箱',
            'foreign_statement_name' => '請輸入國外卡英文帳單名稱',
            'store_city' => '請選擇縣市',
            'store_district' => '請選擇行政區',
            'store_address' => '請輸入商店聯絡地址',
            'contact_name' => '請輸入聯絡人名稱',
            'industry' => '請選擇產業類別',
            'product_type' => '請選擇販售商品類型',
            'guarantee_type' => '請選擇履約保證類型',
            'average_order_amount' => '請輸入平均客單價',
            'store_description' => '請輸入商店營運說明',
        ];

        foreach ($required as $field => $message) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = $message;
            }
        }

        if (!in_array(($data['store_type'] ?? ''), ['online', 'physical'], true)) {
            $errors['store_type'] = '請選擇商店類型';
        }
        if (!empty($data['store_email']) && !filter_var($data['store_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['store_email'] = '請輸入有效的商店電子信箱';
        }
        if (trim((string) ($data['contact_phone'] ?? '')) === '' && trim((string) ($data['contact_mobile'] ?? '')) === '') {
            $errors['contact_mobile'] = '聯絡人電話與手機號碼請擇一填寫';
        }
        if (trim((string) ($data['contact_mobile'] ?? '')) !== '' && !preg_match('/^09\d{2}-?\d{3}-?\d{3}$/', trim((string) $data['contact_mobile']))) {
            $errors['contact_mobile'] = '請輸入有效的台灣手機號碼';
        }

        $ratios = $this->deliveryRatios($data);
        if (array_sum($ratios) !== 100) {
            $errors['delivery_ratios'] = '商品交付型態占比總和需為 100%';
        }

        $storeUrlType = $data['store_url_type'] ?? 'url';
        if ($storeUrlType === 'url') {
            $storeUrl = trim((string) ($data['store_url'] ?? ''));
            if ($storeUrl === '' || !filter_var($storeUrl, FILTER_VALIDATE_URL)) {
                $errors['store_url'] = '請輸入有效的商店網址';
            }
        }

        $paymentTools = $data['payment_tools'] ?? [];
        if (!is_array($paymentTools) || count($paymentTools) === 0) {
            $errors['payment_tools'] = '至少啟用一種支付工具';
        }

        return $errors;
    }

    private function deliveryRatios(array $data): array
    {
        return [
            'prepaid' => max(0, min(100, (int) ($data['ratio_prepaid'] ?? 0))),
            'non_prepaid' => max(0, min(100, (int) ($data['ratio_non_prepaid'] ?? 0))),
            'deferred' => max(0, min(100, (int) ($data['ratio_deferred'] ?? 0))),
            'voucher' => max(0, min(100, (int) ($data['ratio_voucher'] ?? 0))),
        ];
    }

    private function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect($this->baseUrl('/login'));
        }
    }

    private function baseUrl(string $path = ''): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        return $base . $path;
    }

    private function clearCurrentMemberSession(): void
    {
        $memberId = (int) ($_SESSION['member']['id'] ?? 0);
        if ($memberId > 0) {
            $this->member->clearActiveSession($memberId, session_id());
        }
    }

    private function hasActiveMemberSession(array $member): bool
    {
        $memberId = (int) ($member['id'] ?? 0);
        $activeSessionId = (string) ($member['active_session_id'] ?? '');
        if ($activeSessionId === '') {
            return false;
        }

        $lastSeenAt = strtotime((string) ($member['active_session_last_seen_at'] ?? '')) ?: 0;
        if ($lastSeenAt <= 0 || (time() - $lastSeenAt) > max(60, MEMBER_SESSION_TIMEOUT_SECONDS)) {
            if ($memberId > 0) {
                $this->member->clearAnyActiveSession($memberId);
            }
            return false;
        }

        if (!$this->sessionFileExists($activeSessionId)) {
            if ($memberId > 0) {
                $this->member->clearAnyActiveSession($memberId);
            }
            return false;
        }

        return true;
    }

    private function sessionFileExists(string $sessionId): bool
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

    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
