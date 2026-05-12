<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 會員管理系統 API Routes
|--------------------------------------------------------------------------
*/

// ── 公開路由（不需驗證） ──────────────────────────────
Route::prefix('members')->group(function () {
    Route::post('/register',        [MemberController::class, 'register']);  // 會員註冊
    Route::get('/verify/{token}',   [MemberController::class, 'verify']);    // Email 驗證
});

// ── 後台管理路由（實際上線時加上 auth:sanctum middleware） ──
Route::prefix('admin')->group(function () {
    Route::get('/members',                   [AdminController::class, 'index']);    // 會員列表
    Route::get('/stats',                     [AdminController::class, 'stats']);    // 統計
    Route::patch('/members/{id}/approve',    [AdminController::class, 'approve']); // 審核通過
    Route::patch('/members/{id}/suspend',    [AdminController::class, 'suspend']); // 停用
    Route::delete('/members/{id}',           [AdminController::class, 'destroy']); // 刪除
});
