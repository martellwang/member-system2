<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    /**
     * POST /api/members/register
     * 會員線上自助註冊
     */
    public function register(Request $request): JsonResponse
    {
        $type = $request->input('type');

        $rules = [
            'type'     => ['required', Rule::in(['personal', 'company'])],
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:members,email'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
        ];

        if ($type === 'personal') {
            $rules['id_number'] = ['required', 'string', 'regex:/^[A-Z][12][0-9]{8}$/', 'unique:members,id_number'];
            $rules['birth_date'] = ['nullable', 'date'];
            $rules['gender']    = ['nullable', Rule::in(['male', 'female', 'other'])];
        } else {
            $rules['tax_id']       = ['required', 'string', 'regex:/^\d{8}$/', 'unique:members,tax_id'];
            $rules['company_name'] = ['required', 'string', 'max:200'];
            $rules['website']      = ['nullable', 'url', 'max:255'];
            $rules['industry']     = ['nullable', 'string', 'max:50'];
        }

        $validated = $request->validate($rules);
        $validated['password']             = Hash::make($validated['password']);
        $validated['status']               = 'pending';
        $validated['email_verified_token'] = Str::random(64);

        $member = Member::create($validated);

        // TODO: 發送驗證信
        // Mail::to($member->email)->send(new VerifyEmailMail($member));

        return response()->json([
            'message' => '註冊成功，請至信箱完成驗證。',
            'id'      => $member->id,
        ], 201);
    }

    /**
     * GET /api/members/verify/{token}
     * Email 驗證
     */
    public function verify(string $token): JsonResponse
    {
        $member = Member::where('email_verified_token', $token)->first();

        if (!$member) {
            return response()->json(['message' => '驗證連結無效或已過期。'], 404);
        }

        $member->update([
            'status'               => 'active',
            'email_verified_at'    => now(),
            'email_verified_token' => null,
        ]);

        return response()->json(['message' => '信箱驗證成功，帳號已啟用！']);
    }
}
