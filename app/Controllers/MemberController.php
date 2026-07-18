<?php

namespace Controllers;

use Core\Controller;
use Models\Member;

class MemberController extends Controller
{
    private Member $member;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->member = new Member();
    }

    /** GET /register — 顯示註冊頁 */
    public function registerPage(): void
    {
        $this->render('member.register', [
            'title' => '會員註冊',
            'googleSignup' => $_SESSION['google_signup'] ?? null,
        ]);
    }

    /** POST /api/members/register — 處理註冊 */
    public function register(): void
    {
        $data   = $this->requestData();
        $errors = $this->validate($data);

        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        if (($data['type'] ?? '') === 'personal') {
            $data['line_id'] = trim($data['line_id'] ?? '');
            $data['birth_date'] = $this->parseRocDate($data['birth_date'] ?? '');
            $data['id_issue_date'] = $this->parseRocDate($data['id_issue_date'] ?? '');
            $uploadErrors = [];
            $frontPath = $this->storeIdDocument('id_card_front', $uploadErrors);
            $backPath = $this->storeIdDocument('id_card_back', $uploadErrors);

            if ($uploadErrors) {
                $this->json(['errors' => $uploadErrors], 422);
                return;
            }

            $data['id_card_front_path'] = $frontPath;
            $data['id_card_back_path'] = $backPath;
        } else {
            $data['line_id'] = null;
            $data['id_card_front_path'] = null;
            $data['id_card_back_path'] = null;
        }

        $isGoogleSignup = !empty($data['google_id']);

        // 密碼加密。Google 註冊不需使用者另設密碼，系統產生不可預期密碼保留欄位完整性。
        $data['password']             = password_hash($data['password'] ?: bin2hex(random_bytes(24)), PASSWORD_BCRYPT);
        $data['status']               = 'pending';
        $data['auth_provider']        = $isGoogleSignup ? 'google' : 'local';
        $data['email_verified_token'] = $isGoogleSignup ? null : bin2hex(random_bytes(32));
        $data['email_verified_at']    = $isGoogleSignup ? date('Y-m-d H:i:s') : null;
        $data['created_at']           = date('Y-m-d H:i:s');
        $data['updated_at']           = date('Y-m-d H:i:s');

        // 移除非資料庫欄位
        unset($data['password_confirm']);

        $id = $this->member->insert($data);
        $verifyUrl = $this->baseUrl("/verify/{$data['email_verified_token']}");
        if ($isGoogleSignup) {
            unset($_SESSION['google_signup']);
        }

        // TODO: 寄送驗證信
        // mail($data['email'], '帳號驗證', "請點擊以下連結驗證：" . $verifyUrl);

        $this->json([
            'message' => $isGoogleSignup ? 'Google 帳號註冊成功，請等待後台審核。' : '註冊成功，請至信箱完成驗證。',
            'id' => $id,
            'verification_url' => (!$isGoogleSignup && APP_ENV === 'development') ? $verifyUrl : null,
        ], 201);
    }

    /** GET /auth/google — 開始 Google 註冊 */
    public function googleStart(): void
    {
        if (!GOOGLE_CLIENT_ID || !GOOGLE_CLIENT_SECRET) {
            $this->render('member.verify', [
                'title' => '尚未設定 Google 註冊',
                'success' => false,
                'message' => '請先在環境變數設定 GOOGLE_CLIENT_ID 與 GOOGLE_CLIENT_SECRET。',
            ]);
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => $this->baseUrl('/auth/google/callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    }

    /** GET /auth/google/callback — Google 註冊回呼 */
    public function googleCallback(): void
    {
        if (($_GET['state'] ?? '') !== ($_SESSION['google_oauth_state'] ?? '')) {
            $this->render('member.verify', [
                'title' => 'Google 驗證失敗',
                'success' => false,
                'message' => 'Google 驗證狀態不一致，請重新操作。',
            ]);
            return;
        }

        unset($_SESSION['google_oauth_state']);

        if (!empty($_GET['error']) || empty($_GET['code'])) {
            $this->render('member.verify', [
                'title' => 'Google 註冊已取消',
                'success' => false,
                'message' => '未取得 Google 授權，請重新操作。',
            ]);
            return;
        }

        $token = $this->googleToken((string) $_GET['code']);
        if (!$token || empty($token['access_token'])) {
            $this->render('member.verify', [
                'title' => 'Google 驗證失敗',
                'success' => false,
                'message' => '無法取得 Google 存取權杖。',
            ]);
            return;
        }

        $profile = $this->googleUserInfo($token['access_token']);
        if (!$profile || empty($profile['email'])) {
            $this->render('member.verify', [
                'title' => 'Google 驗證失敗',
                'success' => false,
                'message' => '無法取得 Google 帳號資料。',
            ]);
            return;
        }

        $_SESSION['google_signup'] = [
            'google_id' => $profile['sub'] ?? '',
            'email' => $profile['email'],
            'name' => $profile['name'] ?? '',
        ];

        $this->redirect($this->sitePath('/register'));
    }

    /** GET /verify/{token} — Email 驗證 */
    public function verify(string $token): void
    {
        $member = $this->member->findByToken($token);

        if (!$member) {
            $this->render('member.verify', [
                'title' => '驗證失敗',
                'success' => false,
                'message' => '驗證連結無效或已過期。',
            ]);
            return;
        }

        $this->member->update((int) $member['id'], [
            'status'               => 'active',
            'email_verified_at'    => date('Y-m-d H:i:s'),
            'email_verified_token' => null,
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        $this->render('member.verify', [
            'title' => '驗證成功',
            'success' => true,
            'message' => '信箱驗證成功，帳號已啟用！',
        ]);
    }

    /** 表單驗證 */
    private function validate(array $data): array
    {
        $errors = [];
        $type   = $data['type'] ?? '';

        if (!in_array($type, ['personal', 'company'])) {
            $errors['type'] = '請選擇會員類型';
        }
        if (empty($data['name'])) {
            $errors['name'] = '請輸入姓名';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '請輸入有效的電子郵件';
        } elseif ($this->member->emailExists($data['email'])) {
            $errors['email'] = '此電子郵件已被註冊';
        }
        if (empty($data['google_id']) && (empty($data['password']) || strlen($data['password']) < 8)) {
            $errors['password'] = '密碼至少需要 8 位字元';
        } elseif (!empty($data['password']) && strlen((string) $data['password']) < 8) {
            $errors['password'] = '密碼至少需要 8 位字元';
        }
        if (empty($data['mobile_phone'])) {
            $errors['mobile_phone'] = '請輸入手機電話';
        }
        if (empty($data['contact_address'])) {
            $errors['contact_address'] = '請輸入聯絡地址';
        }

        if ($type === 'personal') {
            $idno = strtoupper($data['id_number'] ?? '');
            if (!$this->isValidTaiwanIdNumber($idno)) {
                $errors['id_number'] = '請輸入有效的身分證號（含檢核碼）';
            } elseif ($this->member->idNumberExists($idno)) {
                $errors['id_number'] = '此身分證號已被註冊';
            }
            $this->validateIdDocument('id_card_front', 'id_card_front', $errors);
            $this->validateIdDocument('id_card_back', 'id_card_back', $errors);
            if (!$this->parseRocDate($data['id_issue_date'] ?? '')) {
                $errors['id_issue_date'] = '請輸入有效的民國發證日期，例如 113/01/02';
            }
            if (empty($data['id_issue_place'])) {
                $errors['id_issue_place'] = '請選擇身分證發證地點';
            }
            if (!in_array($data['id_issue_type'] ?? '', ['first', 'replace', 'renew'], true)) {
                $errors['id_issue_type'] = '請選擇身分證補領換類別';
            }
        } else {
            $taxId = $data['tax_id'] ?? '';
            if (!preg_match('/^\d{8}$/', $taxId)) {
                $errors['tax_id'] = '統一編號需為 8 碼數字';
            } elseif ($this->member->taxIdExists($taxId)) {
                $errors['tax_id'] = '此統一編號已被註冊';
            }
            if (empty($data['company_name'])) {
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

    private function googleToken(string $code): ?array
    {
        $body = http_build_query([
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => $this->baseUrl('/auth/google/callback'),
            'grant_type' => 'authorization_code',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                'content' => $body,
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
        return is_string($response) ? json_decode($response, true) : null;
    }

    private function googleUserInfo(string $accessToken): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$accessToken}\r\nAccept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents('https://www.googleapis.com/oauth2/v3/userinfo', false, $context);
        return is_string($response) ? json_decode($response, true) : null;
    }

    private function requestData(): array
    {
        if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
            return $_POST;
        }

        return $this->input();
    }

    private function validateIdDocument(string $field, string $errorKey, array &$errors): void
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[$errorKey] = '請上傳身分證正反面電子檔';
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[$errorKey] = '檔案上傳失敗，請重新選擇檔案';
            return;
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $errors[$errorKey] = '檔案大小不可超過 5MB';
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $errors[$errorKey] = '僅支援 JPG、PNG 或 PDF 檔案';
        }
    }

    private function storeIdDocument(string $field, array &$errors): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $mime = mime_content_type($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ];

        if (!isset($extensions[$mime])) {
            $errors[$field] = '僅支援 JPG、PNG 或 PDF 檔案';
            return null;
        }

        $directory = BASE_PATH . '/storage/id-documents';
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            $errors[$field] = '無法建立上傳目錄';
            return null;
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $errors[$field] = '無法儲存上傳檔案';
            return null;
        }

        return 'storage/id-documents/' . $filename;
    }

    private function baseUrl(string $path = ''): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? parse_url(APP_URL, PHP_URL_HOST) ?? 'localhost';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        return "{$scheme}://{$host}{$base}{$path}";
    }

    private function sitePath(string $path = ''): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        return $base . $path;
    }
}
