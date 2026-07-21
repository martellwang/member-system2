<?php

namespace Controllers;

use Core\Controller;
use Core\Mailer;
use Models\Member;
use Support\TaiwanAddress;

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

    /** GET /register/complete — 註冊資料填寫完成頁 */
    public function registerCompletePage(): void
    {
        $this->render('member.register-complete', [
            'title' => '註冊資料填寫完成',
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

        $data['mobile_phone'] = $this->normalizeTaiwanMobile($data['mobile_phone'] ?? '');
        $data['phone_area_code'] = trim($data['phone_area_code'] ?? '');
        $data['phone'] = trim($data['phone'] ?? '');
        $data['contact_city'] = trim((string) ($data['contact_city'] ?? ''));
        $data['contact_district'] = trim((string) ($data['contact_district'] ?? ''));
        $data['contact_address_line'] = trim((string) ($data['contact_address_line'] ?? ''));
        $data['contact_address'] = TaiwanAddress::compose(
            $data['contact_city'],
            $data['contact_district'],
            $data['contact_address_line']
        );

        if (($data['type'] ?? '') === 'personal') {
            $data['id_number'] = strtoupper(trim((string) ($data['id_number'] ?? '')));
            $data['gender'] = $this->genderFromTaiwanIdNumber($data['id_number']);
            $data['line_id'] = trim($data['line_id'] ?? '');
            $data['birth_date'] = $this->parseRocDate($data['birth_date'] ?? '');
            $data['id_issue_date'] = $this->parseRocDate($data['id_issue_date'] ?? '');
            $uploadErrors = [];
            $frontPath = $this->storeIdDocument('id_card_front', $uploadErrors);
            $backPath = $this->storeIdDocument('id_card_back', $uploadErrors);
            $secondIdDocPath = $this->storeIdDocument('second_id_doc', $uploadErrors);
            $bankBookCoverPath = $this->storeIdDocument('bank_book_cover', $uploadErrors);

            if ($uploadErrors) {
                $this->json(['errors' => $uploadErrors], 422);
                return;
            }

            $data['id_card_front_path'] = $frontPath;
            $data['id_card_back_path'] = $backPath;
            $data['second_id_doc_path'] = $secondIdDocPath;
            $data['bank_book_cover_path'] = $bankBookCoverPath;
            $data['company_owner_id_card_front_path'] = null;
            $data['company_owner_id_card_back_path'] = null;
            $data['company_registration_doc_paths'] = null;
        } else {
            $uploadErrors = [];
            $ownerFrontPath = $this->storeIdDocument('company_owner_id_card_front', $uploadErrors);
            $ownerBackPath = $this->storeIdDocument('company_owner_id_card_back', $uploadErrors);
            $registrationDocPaths = $this->storeMultipleIdDocuments('company_registration_docs', $uploadErrors);
            $bankBookCoverPath = $this->storeIdDocument('bank_book_cover', $uploadErrors);

            if ($uploadErrors) {
                $this->json(['errors' => $uploadErrors], 422);
                return;
            }

            $data['line_id'] = null;
            $data['id_card_front_path'] = null;
            $data['id_card_back_path'] = null;
            $data['second_id_doc_path'] = null;
            $data['bank_book_cover_path'] = $bankBookCoverPath;
            $data['gender'] = null;
            $data['company_owner_id_card_front_path'] = $ownerFrontPath;
            $data['company_owner_id_card_back_path'] = $ownerBackPath;
            $data['company_registration_doc_paths'] = json_encode($registrationDocPaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $data['is_dealer'] = !empty($data['is_dealer']) ? 1 : 0;
        }

        $isGoogleSignup = !empty($data['google_id']);
        $defaultLoginId = $this->defaultMemberLoginId($data);

        // Google 註冊不需本機密碼；非 Google 註冊使用會員身分識別碼作為初始密碼。
        $data['password']             = password_hash($isGoogleSignup ? bin2hex(random_bytes(24)) : $defaultLoginId, PASSWORD_BCRYPT);
        $data['status']               = $isGoogleSignup ? 'pending' : 'email_unverified';
        $data['auth_provider']        = $isGoogleSignup ? 'google' : 'local';
        $data['email_verified_token'] = $isGoogleSignup ? null : bin2hex(random_bytes(32));
        $data['email_verified_at']    = $isGoogleSignup ? date('Y-m-d H:i:s') : null;
        $data['created_at']           = date('Y-m-d H:i:s');
        $data['updated_at']           = date('Y-m-d H:i:s');

        // 移除非資料庫欄位
        unset($data['password_confirm']);

        $id = $this->member->insert($data);
        $this->member->assignMemberCode($id);
        $verifyUrl = $this->baseUrl("/verify/{$data['email_verified_token']}");
        if ($isGoogleSignup) {
            unset($_SESSION['google_signup']);
        }

        if (!$isGoogleSignup) {
            $this->sendMemberEmailVerificationEmail($data['email'], $data['name'], $verifyUrl, $defaultLoginId);
        }

        $this->json([
            'message' => $isGoogleSignup ? 'Google 帳號註冊成功，請等待後台審核。' : '註冊資料已送出，請至信箱完成驗證。初始登入帳號與密碼已寄至信箱。',
            'id' => $id,
            'verification_url' => (!$isGoogleSignup && APP_ENV === 'development') ? $verifyUrl : null,
            'complete_url' => $this->sitePath('/register/complete'),
        ], 201);
    }

    /** GET /auth/google — 開始 Google 註冊或登入 */
    public function googleStart(): void
    {
        if (!GOOGLE_CLIENT_ID || !GOOGLE_CLIENT_SECRET) {
            $this->render('member.verify', [
                'title' => '尚未設定 Google 登入',
                'success' => false,
                'message' => '請先在環境變數設定 GOOGLE_CLIENT_ID 與 GOOGLE_CLIENT_SECRET。',
            ]);
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_mode'] = ($_GET['mode'] ?? '') === 'login' ? 'login' : 'signup';
        $_SESSION['google_oauth_login_type'] = ($_GET['member_type'] ?? '') === 'company' ? 'company' : 'personal';
        $_SESSION['google_oauth_tax_id'] = preg_replace('/\D+/', '', (string) ($_GET['tax_id'] ?? ''));

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

    /** GET /auth/google/callback — Google 註冊或登入回呼 */
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
        $mode = $_SESSION['google_oauth_mode'] ?? 'signup';
        $loginType = $_SESSION['google_oauth_login_type'] ?? 'personal';
        $loginTaxId = $_SESSION['google_oauth_tax_id'] ?? '';
        unset($_SESSION['google_oauth_mode']);
        unset($_SESSION['google_oauth_login_type']);
        unset($_SESSION['google_oauth_tax_id']);

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

        if ($mode === 'login') {
            $this->loginWithGoogleProfile($profile, $loginType, $loginTaxId);
            return;
        }

        $_SESSION['google_signup'] = [
            'google_id' => $profile['sub'] ?? '',
            'email' => $profile['email'],
            'name' => $profile['name'] ?? '',
            'member_type' => $loginType,
        ];

        $this->redirect($this->sitePath('/register'));
    }

    private function loginWithGoogleProfile(array $profile, string $memberType = 'personal', string $taxId = ''): void
    {
        $memberType = in_array($memberType, ['personal', 'company'], true) ? $memberType : 'personal';
        $googleId = (string) ($profile['sub'] ?? '');
        $taxId = preg_replace('/\D+/', '', $taxId);

        if ($memberType === 'company' && !preg_match('/^\d{8}$/', $taxId)) {
            $this->render('member.verify', [
                'title' => 'Google 登入失敗',
                'success' => false,
                'message' => '公司法人使用 Google 登入前，請先輸入 8 碼統一編號。',
            ]);
            return;
        }

        $member = false;
        if ($googleId) {
            $member = $memberType === 'company'
                ? $this->member->findCompanyByGoogleIdAndTaxId($googleId, $taxId)
                : $this->member->findByGoogleIdAndType($googleId, $memberType);
        }

        if (!$member) {
            $memberLabel = $memberType === 'company' ? '公司法人' : '個人會員';
            $this->render('member.verify', [
                'title' => 'Google 登入失敗',
                'success' => false,
                'message' => "找不到已綁定的{$memberLabel}帳號，請先使用 Google 帳號完成會員註冊。",
            ]);
            return;
        }

        if (($member['status'] ?? '') === 'suspended') {
            $this->render('member.verify', [
                'title' => '帳號已停用',
                'success' => false,
                'message' => '帳號已停用，請聯絡管理員。',
            ]);
            return;
        }

        if ($this->hasActiveMemberSession($member)) {
            $this->member->recordDuplicateLoginAttempt((int) $member['id'], $this->clientIp());
            $this->render('member.verify', [
                'title' => '會員登入被阻擋',
                'success' => false,
                'message' => '此會員帳號目前已在其他裝置或瀏覽器登入，系統已阻止第二個相同身分同時登入。',
            ]);
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

        $this->redirect($this->sitePath('/member'));
    }

    /** GET /verify/{token} — Email 驗證與會員密碼設定頁 */
    public function verify(string $token): void
    {
        $member = $this->member->findByToken($token);
        if (!$this->isValidMemberPasswordSetupToken($member ?: null)) {
            $this->render('member.verify', [
                'title' => '信箱驗證失敗',
                'success' => false,
                'message' => '驗證連結無效或已使用。',
            ]);
            return;
        }

        $this->member->update((int) $member['id'], [
            'status' => 'pending',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verified_token' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->render('member.verify', [
            'title' => '信箱驗證完成',
            'success' => true,
            'message' => '信箱驗證完成，會員資料已進入後台待審核狀態。',
        ]);
    }

    /** POST /api/members/setup-password/{token} — 完成 Email 驗證與密碼設定 */
    public function setupPassword(string $token): void
    {
        $member = $this->member->findByToken($token);
        if (!$this->isValidMemberPasswordSetupToken($member ?: null)) {
            $this->json(['message' => '驗證連結無效或已使用。'], 404);
            return;
        }

        $data = $this->input();
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['password_confirm'] ?? '');
        $errors = [];

        if (strlen($password) < 8) {
            $errors['password'] = '密碼至少需要 8 位字元。';
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = '兩次輸入的密碼不一致。';
        }

        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $this->member->update((int) $member['id'], [
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'status' => 'pending',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verified_token' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message' => '信箱驗證與密碼設定完成，請等待後台審核。',
            'login_url' => $this->sitePath('/login'),
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
        }
        if (!$this->isValidTaiwanMobile($data['mobile_phone'] ?? '')) {
            $errors['mobile_phone'] = '請輸入有效的台灣手機號碼';
        }
        $errors = array_merge($errors, TaiwanAddress::validateParts($data));

        if ($type === 'personal') {
            $idno = strtoupper(trim((string) ($data['id_number'] ?? '')));
            if (!$this->isValidTaiwanIdNumber($idno)) {
                $errors['id_number'] = '請輸入有效的身分證號（含檢核碼）';
            } elseif (empty($errors['email']) && $this->member->personalIdentityExists($data['email'], $idno)) {
                $errors['id_number'] = '此電子郵件與身分證號組合已被註冊';
            }
            $this->validateIdDocument('id_card_front', 'id_card_front', $errors);
            $this->validateIdDocument('id_card_back', 'id_card_back', $errors);
            $this->validateIdDocument('second_id_doc', 'second_id_doc', $errors, '請上傳第二證件電子檔');
            $this->validateIdDocument('bank_book_cover', 'bank_book_cover', $errors, '請上傳申請人名稱的銀行帳戶封面電子檔');
            if (!$this->parseRocDate($data['id_issue_date'] ?? '')) {
                $errors['id_issue_date'] = '請輸入有效的民國發證日期，例如 113/01/02';
            }
            if (!$this->parseRocDate($data['birth_date'] ?? '')) {
                $errors['birth_date'] = '請輸入有效的民國出生日期，例如 083/05/15';
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
            } elseif (empty($errors['email']) && $this->member->companyIdentityExists($data['email'], $taxId)) {
                $errors['tax_id'] = '此電子郵件與統一編號組合已被註冊';
            }
            if (empty($data['company_name'])) {
                $errors['company_name'] = '請輸入公司名稱';
            }
            $this->validateIdDocument('company_owner_id_card_front', 'company_owner_id_card_front', $errors, '請上傳公司負責人身分證正面電子檔');
            $this->validateIdDocument('company_owner_id_card_back', 'company_owner_id_card_back', $errors, '請上傳公司負責人身分證反面電子檔');
            $this->validateMultipleIdDocuments('company_registration_docs', 'company_registration_docs', $errors, '請上傳 1 至 6 份公司登記證書電子檔');
            $this->validateIdDocument('bank_book_cover', 'bank_book_cover', $errors, '請上傳公司名稱的銀行帳戶封面電子檔');
        }

        return $errors;
    }

    private function genderFromTaiwanIdNumber(string $idno): ?string
    {
        $idno = strtoupper(trim($idno));
        return match ($idno[1] ?? '') {
            '1' => 'male',
            '2' => 'female',
            default => null,
        };
    }

    private function defaultMemberLoginId(array $data): string
    {
        if (($data['type'] ?? '') === 'company') {
            return preg_replace('/\D+/', '', (string) ($data['tax_id'] ?? ''));
        }

        return strtoupper(trim((string) ($data['id_number'] ?? '')));
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

    private function validateIdDocument(string $field, string $errorKey, array &$errors, string $requiredMessage = '請上傳身分證正反面電子檔'): void
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[$errorKey] = $requiredMessage;
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

    private function validateMultipleIdDocuments(string $field, string $errorKey, array &$errors, string $requiredMessage): void
    {
        $files = $_FILES[$field] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) {
            $errors[$errorKey] = $requiredMessage;
            return;
        }

        $count = count(array_filter($files['name'], fn($name) => trim((string) $name) !== ''));
        if ($count < 1 || $count > 6) {
            $errors[$errorKey] = $requiredMessage;
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        foreach ($files['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            if ($error !== UPLOAD_ERR_OK) {
                $errors[$errorKey] = '公司登記證書檔案上傳失敗，請重新選擇檔案';
                return;
            }

            if (($files['size'][$index] ?? 0) > 5 * 1024 * 1024) {
                $errors[$errorKey] = '公司登記證書單檔大小不可超過 5MB';
                return;
            }

            $mime = mime_content_type($files['tmp_name'][$index]);
            if (!in_array($mime, $allowed, true)) {
                $errors[$errorKey] = '公司登記證書僅支援 JPG、PNG 或 PDF 檔案';
                return;
            }
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

    private function storeMultipleIdDocuments(string $field, array &$errors): array
    {
        $files = $_FILES[$field] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) {
            return [];
        }

        $paths = [];
        foreach ($files['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $singleField = "{$field}_{$index}";
            $_FILES[$singleField] = [
                'name' => $files['name'][$index],
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index],
            ];

            $path = $this->storeIdDocument($singleField, $errors);
            unset($_FILES[$singleField]);

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function isValidMemberPasswordSetupToken(?array $member): bool
    {
        if (!$member || empty($member['email_verified_token'])) {
            return false;
        }

        if (!empty($member['email_verified_at'])) {
            return false;
        }

        return in_array($member['status'] ?? '', ['email_unverified', 'pending'], true);
    }

    private function sendMemberEmailVerificationEmail(string $email, string $name, string $verifyUrl, string $defaultLoginId): bool
    {
        $body = implode("\r\n", [
            "{$name} 您好：",
            '',
            '您的會員註冊資料已送出，請點擊以下連結完成信箱驗證：',
            $verifyUrl,
            '',
            '完成信箱驗證後，會員資料會進入後台待審核狀態。',
            '',
            '您的初始登入資料如下：',
            "登入帳號：{$defaultLoginId}",
            "初始密碼：{$defaultLoginId}",
            '',
            '公司會員的登入帳號為統一編號；個人會員的登入帳號為身分證字號（英文大寫）。',
            '為保障帳號安全，首次登入後建議至會員中心變更密碼。',
            '',
            '若您沒有申請會員註冊，請忽略此信。',
        ]);
        return Mailer::send($email, '會員信箱驗證與初始登入資料', $body);
    }

    private function baseUrl(string $path = ''): string
    {
        return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
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

    private function sitePath(string $path = ''): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base === '.' || $base === '/') {
            $base = '';
        }
        return $base . $path;
    }
}
