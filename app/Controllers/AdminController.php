<?php

namespace Controllers;

use Core\Controller;
use Models\Member;

class AdminController extends Controller
{
    private Member $member;

    public function __construct()
    {
        $this->member = new Member();
    }

    /** GET /admin — 顯示後台頁 */
    public function index(): void
    {
        $this->render('admin.index', ['title' => '後台管理']);
    }

    /** GET /api/admin/members — 會員列表（支援篩選 & 搜尋） */
    public function list(): void
    {
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
        $this->json($this->member->stats());
    }

    /** PATCH /api/admin/members/{id}/approve — 審核通過 */
    public function approve(string $id): void
    {
        $this->member->update((int) $id, [
            'status'     => 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['message' => '已審核通過。']);
    }

    /** PATCH /api/admin/members/{id}/suspend — 停用帳號 */
    public function suspend(string $id): void
    {
        $this->member->update((int) $id, [
            'status'     => 'pending',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['message' => '帳號已停用。']);
    }

    /** DELETE /api/admin/members/{id} — 刪除 */
    public function destroy(string $id): void
    {
        $this->member->delete((int) $id);
        $this->json(['message' => '已刪除。']);
    }
}
