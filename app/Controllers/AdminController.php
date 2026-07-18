<?php

namespace Controllers;

use Core\Controller;
use Models\Member;

class AdminController extends Controller
{
    private Member $member;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->member = new Member();
    }

    /** GET /admin/login — 顯示登入頁 */
    public function loginPage(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->baseUrl('/admin'));
        }
        $this->render('admin.login', ['title' => '管理員登入']);
    }

    /** GET /admin — 顯示後台頁 */
    public function index(): void
    {
        $this->requireLogin();
        $this->render('admin.index', ['title' => '後台管理']);
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
        ]);
    }

    /** POST /api/admin/login — 管理員登入 */
    public function login(): void
    {
        $data = $this->input();
        $email = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($email !== ADMIN_EMAIL || $password !== ADMIN_PASSWORD) {
            $this->json(['message' => '帳號或密碼錯誤。'], 422);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'email' => ADMIN_EMAIL,
            'logged_in_at' => date('Y-m-d H:i:s'),
        ];

        $this->json(['message' => '登入成功。']);
    }

    /** POST /api/admin/logout — 管理員登出 */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->json(['message' => '已登出。']);
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

        // 隱藏敏感欄位
        $members = array_map(function ($m) {
            unset($m['password'], $m['email_verified_token']);
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
        $errors = $this->validateMember($data, $memberId);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $birthDate = $this->parseRocDate($data['birth_date'] ?? '');
        $issueDate = $this->parseRocDate($data['id_issue_date'] ?? '');

        $type = $data['type'];
        $payload = [
            'type' => $type,
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone'] ?? ''),
            'mobile_phone' => trim($data['mobile_phone'] ?? ''),
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
            ];
        }

        $this->member->update($memberId, $payload);
        $this->json(['message' => '會員資料已更新。']);
    }

    /** POST /api/admin/members/{id}/approve — 審核通過 */
    public function approve(string $id): void
    {
        if (!$this->requireApiLogin()) {
            return;
        }
        if (!$this->member->find((int) $id)) {
            $this->json(['message' => '找不到會員。'], 404);
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
        return !empty($_SESSION['admin']['email']);
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

    private function baseUrl(string $path = ''): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        return $base . $path;
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
        } elseif ($this->member->emailExists($email, $memberId)) {
            $errors['email'] = '此電子郵件已被其他會員使用';
        }
        if (!empty($data['password']) && strlen((string) $data['password']) < 8) {
            $errors['password'] = '新密碼至少需要 8 位字元';
        }
        if (trim($data['mobile_phone'] ?? '') === '') {
            $errors['mobile_phone'] = '請輸入手機電話';
        }
        if (trim($data['contact_address'] ?? '') === '') {
            $errors['contact_address'] = '請輸入聯絡地址';
        }

        if ($type === 'personal') {
            $idno = strtoupper(trim($data['id_number'] ?? ''));
            if (!$this->isValidTaiwanIdNumber($idno)) {
                $errors['id_number'] = '請輸入有效的身分證號（含檢核碼）';
            } elseif ($this->member->idNumberExists($idno, $memberId)) {
                $errors['id_number'] = '此身分證號已被其他會員使用';
            }
            if (!$this->parseRocDate($data['id_issue_date'] ?? '')) {
                $errors['id_issue_date'] = '請輸入有效的民國發證日期，例如 113/01/02';
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
            } elseif ($this->member->taxIdExists($taxId, $memberId)) {
                $errors['tax_id'] = '此統一編號已被其他會員使用';
            }
            if (trim($data['company_name'] ?? '') === '') {
                $errors['company_name'] = '請輸入公司名稱';
            }
        }

        return $errors;
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
