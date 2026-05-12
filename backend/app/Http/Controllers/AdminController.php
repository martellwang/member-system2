<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * GET /api/admin/members
     * 取得所有會員列表（含篩選 & 搜尋）
     */
    public function index(Request $request): JsonResponse
    {
        $query = Member::query();

        // 類型篩選
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 搜尋
        if ($request->filled('q')) {
            $kw = $request->q;
            $query->where(function ($q) use ($kw) {
                $q->where('name',         'like', "%{$kw}%")
                  ->orWhere('email',       'like', "%{$kw}%")
                  ->orWhere('id_number',   'like', "%{$kw}%")
                  ->orWhere('tax_id',      'like', "%{$kw}%")
                  ->orWhere('company_name','like', "%{$kw}%");
            });
        }

        $members = $query->orderByDesc('created_at')->get();

        return response()->json($members);
    }

    /**
     * GET /api/admin/stats
     * 取得統計數字
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total'    => Member::count(),
            'personal' => Member::personal()->count(),
            'company'  => Member::company()->count(),
            'pending'  => Member::pending()->count(),
            'active'   => Member::active()->count(),
        ]);
    }

    /**
     * PATCH /api/admin/members/{id}/approve
     * 審核通過
     */
    public function approve(int $id): JsonResponse
    {
        $member = Member::findOrFail($id);
        $member->update(['status' => 'active']);
        return response()->json(['message' => '已審核通過。']);
    }

    /**
     * PATCH /api/admin/members/{id}/suspend
     * 停用帳號
     */
    public function suspend(int $id): JsonResponse
    {
        $member = Member::findOrFail($id);
        $member->update(['status' => 'pending']);
        return response()->json(['message' => '帳號已停用。']);
    }

    /**
     * DELETE /api/admin/members/{id}
     * 刪除會員
     */
    public function destroy(int $id): JsonResponse
    {
        Member::findOrFail($id)->delete();
        return response()->json(['message' => '已刪除。']);
    }
}
