<?php

namespace Controllers;

use Core\Controller;
use Models\Member;

class MemberController extends Controller
{
    private Member $member;

    public function __construct()
    {
        $this->member = new Member();
    }

    /** GET /register — 顯示註冊頁 */
    public function registerPage(): void
    {
        $this->render('member.register', ['title' => '會員註冊']);
    }

    /** POST /api/members/register — 處理註冊 */
    public function register(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);

        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        // 密碼加密
        $data['password']             = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['status']               = 'pending';
        $data['email_verified_token'] = bin2hex(random_bytes(32));
        $data['created_at']           = date('Y-m-d H:i:s');
        $data['updated_at']           = date('Y-m-d H:i:s');

        // 移除非資料庫欄位
        unset($data['password_confirm']);

        $id = $this->member->insert($data);

        // TODO: 寄送驗證信
        // mail($data['email'], '帳號驗證', "請點擊以下連結驗證：" . APP_URL . "/verify/{$data['email_verified_token']}");

        $this->json(['message' => '註冊成功，請至信箱完成驗證。', 'id' => $id], 201);
    }

    /** GET /verify/{token} — Email 驗證 */
    public function verify(string $token): void
    {
        $member = $this->member->findByToken($token);

        if (!$member) {
            $this->json(['message' => '驗證連結無效或已過期。'], 404);
            return;
        }

        $this->member->update((int) $member['id'], [
            'status'               => 'active',
            'email_verified_at'    => date('Y-m-d H:i:s'),
            'email_verified_token' => null,
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => '信箱驗證成功，帳號已啟用！']);
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
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = '密碼至少需要 8 位字元';
        }

        if ($type === 'personal') {
            $idno = strtoupper($data['id_number'] ?? '');
            if (!preg_match('/^[A-Z][12][0-9]{8}$/', $idno)) {
                $errors['id_number'] = '請輸入有效的身分證號';
            } elseif ($this->member->idNumberExists($idno)) {
                $errors['id_number'] = '此身分證號已被註冊';
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
}
