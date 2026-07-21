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
        $password = (string) ($data['password'] ?? '');
        $memberType = (string) ($data['member_type'] ?? '');
        $idNumber = strtoupper(trim((string) ($data['id_number'] ?? '')));
        $taxId = preg_replace('/\D+/', '', (string) ($data['tax_id'] ?? ''));

        if (!in_array($memberType, ['personal', 'company'], true)) {
            $this->json(['message' => '請選擇登入身分。'], 422);
            return;
        }

        if ($password === '') {
            $this->json(['message' => '請輸入登入密碼。'], 422);
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
            ? $this->member->findPersonalByIdNumber($idNumber)
            : $this->member->findCompanyByTaxId($taxId);

        if (!$member) {
            $this->json(['message' => '帳號或密碼錯誤。'], 422);
            return;
        }

        if (($member['status'] ?? '') === 'email_unverified' || empty($member['email_verified_at'])) {
            $this->json(['message' => '請先至信箱完成驗證。'], 403);
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
            'contact_phone_area_code' => trim((string) ($data['contact_phone_area_code'] ?? '')) ?: null,
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

    /** POST /api/members/stores/{storeId}/invoice-settings - 更新商店電子發票設定 */
    public function updateStoreInvoiceSettings(string $storeId): void
    {
        if (!$this->isLoggedIn()) {
            $this->json(['message' => '請先登入會員。'], 401);
            return;
        }

        $store = $this->memberStore->findByMemberAndId((int) $_SESSION['member']['id'], (int) $storeId);
        if (!$store) {
            $this->json(['message' => '找不到此會員的商店資料。'], 404);
            return;
        }

        $data = $this->input();
        $enabled = filter_var($data['e_invoice_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $autoIssue = filter_var($data['e_invoice_auto_issue'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $center = trim((string) ($data['e_invoice_center'] ?? ''));
        $giftUnit = trim((string) ($data['e_invoice_gift_unit'] ?? ''));
        $delayDays = $data['e_invoice_delay_days'] ?? null;
        $delayDays = $delayDays === null || $delayDays === '' ? null : (int) $delayDays;
        $errors = [];

        if ($center !== '' && mb_strlen($center) > 100) {
            $errors['e_invoice_center'] = '電子發票加值中心名稱不可超過 100 字。';
        }
        if ($giftUnit !== '' && mb_strlen($giftUnit) > 30) {
            $errors['e_invoice_gift_unit'] = '預設發票捐贈單位不可超過 30 字。';
        }
        if ($delayDays !== null && ($delayDays < 1 || $delayDays > 30)) {
            $errors['e_invoice_delay_days'] = '延後開立天數必須介於 1 至 30 天。';
        }

        if ($errors) {
            $this->json(['message' => '電子發票設定資料不正確。', 'errors' => $errors], 422);
            return;
        }

        $this->memberStore->update((int) $storeId, [
            'e_invoice_enabled' => $enabled ? 1 : 0,
            'e_invoice_center' => $center,
            'e_invoice_gift_unit' => $giftUnit,
            'e_invoice_auto_issue' => $autoIssue ? 1 : 0,
            'e_invoice_delay_days' => $autoIssue ? null : $delayDays,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => '電子發票設定已儲存。']);
    }

    /** POST /api/members/stores/{storeId}/transaction-settings - 更新交易限制設定 */
    public function updateStoreTransactionSettings(string $storeId): void
    {
        if (!$this->isLoggedIn()) {
            $this->json(['message' => '請先登入會員。'], 401);
            return;
        }

        $store = $this->memberStore->findByMemberAndId((int) $_SESSION['member']['id'], (int) $storeId);
        if (!$store) {
            $this->json(['message' => '找不到此會員的商店資料。'], 404);
            return;
        }

        $data = $this->input();
        $cardMode = (string) ($data['transaction_card_limit_mode'] ?? 'off');
        $ipMode = (string) ($data['transaction_ip_limit_mode'] ?? 'off');
        $allowedModes = ['off', 'blacklist', 'whitelist'];
        if (!in_array($cardMode, $allowedModes, true) || !in_array($ipMode, $allowedModes, true)) {
            $this->json(['message' => '交易限制模式不正確。'], 422);
            return;
        }

        $this->memberStore->update((int) $storeId, [
            'transaction_amount_limit_enabled' => filter_var($data['transaction_amount_limit_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'expired_refund_enabled' => filter_var($data['expired_refund_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'transaction_card_limit_mode' => $cardMode,
            'transaction_ip_limit_mode' => $ipMode,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => '交易限制設定已儲存。']);
    }

    /** POST /api/members/stores/{storeId}/integration-settings - 更新串接設定 */
    public function updateStoreIntegrationSettings(string $storeId): void
    {
        if (!$this->isLoggedIn()) {
            $this->json(['message' => '請先登入會員。'], 401);
            return;
        }

        $store = $this->memberStore->findByMemberAndId((int) $_SESSION['member']['id'], (int) $storeId);
        if (!$store) {
            $this->json(['message' => '找不到此會員的商店資料。'], 404);
            return;
        }

        $data = $this->input();
        $hashKey = trim((string) ($data['integration_hash_key'] ?? ''));
        $ivKey = trim((string) ($data['integration_iv_key'] ?? ''));
        $notifyUrl = trim((string) ($data['integration_notify_url'] ?? ''));
        $returnUrl = trim((string) ($data['integration_return_url'] ?? ''));
        $allowedIps = trim((string) ($data['integration_allowed_ips'] ?? ''));
        $errors = [];

        foreach (['integration_hash_key' => $hashKey, 'integration_iv_key' => $ivKey] as $field => $value) {
            if ($value !== '' && mb_strlen($value) > 255) {
                $errors[$field] = '串接金鑰不可超過 255 字。';
            }
        }
        foreach (['integration_notify_url' => $notifyUrl, 'integration_return_url' => $returnUrl] as $field => $value) {
            if ($value !== '' && (!$this->isHttpUrl($value) || mb_strlen($value) > 255)) {
                $errors[$field] = '請輸入有效的 HTTP 或 HTTPS 網址。';
            }
        }
        if (mb_strlen($allowedIps) > 2000) {
            $errors['integration_allowed_ips'] = '限定 API 的 IP 設定不可超過 2000 字。';
        }

        $ipLines = $allowedIps === '' ? [] : (preg_split('/\R/', $allowedIps) ?: []);
        $normalizedIps = [];
        foreach ($ipLines as $line) {
            $ip = trim($line);
            if ($ip === '') {
                continue;
            }
            if (!$this->isIpOrCidr($ip)) {
                $errors['integration_allowed_ips'] = '每一行必須是有效的 IP 位址或 CIDR。';
                break;
            }
            $normalizedIps[] = $ip;
        }
        if ($errors) {
            $this->json(['message' => '串接設定資料不正確。', 'errors' => $errors], 422);
            return;
        }

        $apiFlags = [
            'integration_credit_card_api_enabled', 'integration_refund_api_enabled',
            'integration_token_api_enabled', 'integration_non_card_refund_api_enabled',
            'integration_logistics_refund_api_enabled', 'integration_linepay_refund_api_enabled',
            'integration_member_free_api_enabled', 'integration_discount_refund_api_enabled',
            'integration_street_payment_refund_api_enabled',
        ];
        $payload = [
            'integration_hash_key' => $hashKey ?: null,
            'integration_iv_key' => $ivKey ?: null,
            'integration_notify_url' => $notifyUrl ?: null,
            'integration_return_url' => $returnUrl ?: null,
            'integration_test_mode' => filter_var($data['integration_test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'integration_allowed_ips' => $normalizedIps ? implode("\n", $normalizedIps) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        foreach ($apiFlags as $flag) {
            $payload[$flag] = filter_var($data[$flag] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        $this->memberStore->update((int) $storeId, $payload);
        $this->json(['message' => '串接設定已儲存。']);
    }

    private function isHttpUrl(string $value): bool
    {
        $parts = parse_url($value);
        return is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && !empty($parts['host']);
    }

    private function isIpOrCidr(string $value): bool
    {
        $parts = explode('/', $value, 2);
        if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return false;
        }
        if (!isset($parts[1])) {
            return true;
        }
        $max = str_contains($parts[0], ':') ? 128 : 32;
        return ctype_digit($parts[1]) && (int) $parts[1] >= 0 && (int) $parts[1] <= $max;
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
        $contactPhone = trim((string) ($data['contact_phone'] ?? ''));
        $contactMobile = trim((string) ($data['contact_mobile'] ?? ''));
        $contactPhoneAreaCode = trim((string) ($data['contact_phone_area_code'] ?? ''));
        if ($contactPhone === '' && $contactMobile === '') {
            $errors['contact_mobile'] = '聯絡人電話與手機號碼請擇一填寫';
        }
        if ($contactMobile !== '' && !preg_match('/^09\d{2}-?\d{3}-?\d{3}$/', $contactMobile)) {
            $errors['contact_mobile'] = '請輸入有效的台灣手機號碼';
        }
        if ($contactPhone !== '') {
            if (!in_array($contactPhoneAreaCode, ['02', '03', '037', '04', '049', '05', '06', '07', '08', '089', '082', '0826', '0836'], true)) {
                $errors['contact_phone_area_code'] = '請選擇有效的台灣市話區碼';
            }
            if (!preg_match('/^\d{6,8}$/', str_replace('-', '', $contactPhone))) {
                $errors['contact_phone'] = '請輸入 6 至 8 碼的市話號碼';
            }
        }

        $ratios = $this->deliveryRatios($data);
        if (array_sum($ratios) !== 100) {
            $errors['delivery_ratios'] = '商品交付型態占比總和需為 100%';
        }

        $storeUrlType = $data['store_url_type'] ?? 'url';
        if (!in_array($storeUrlType, ['url', 'none'], true)) {
            $errors['store_url_type'] = '請選擇有效的商店網址類型';
        }
        if ($storeUrlType === 'url') {
            $storeUrl = trim((string) ($data['store_url'] ?? ''));
            if (!$this->isCompliantStoreUrl($storeUrl)) {
                $errors['store_url'] = '請輸入有效的商店網址';
            }
        }

        $paymentTools = $data['payment_tools'] ?? [];
        if (!is_array($paymentTools) || count($paymentTools) === 0) {
            $errors['payment_tools'] = '至少啟用一種支付工具';
        }

        return $errors;
    }

    private function isCompliantStoreUrl(string $value): bool
    {
        if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($value);
        return is_array($parts)
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            && trim((string) $parts['host']) !== '';
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
