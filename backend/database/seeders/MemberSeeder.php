<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // 個人用戶測試資料
        Member::insert([
            [
                'type' => 'personal', 'status' => 'active',
                'name' => '王小明', 'email' => 'ming@mail.com', 'phone' => '0912-111-222',
                'password' => Hash::make('password123'),
                'id_number' => 'A123456789', 'birth_date' => '1990-05-15', 'gender' => 'male',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'type' => 'personal', 'status' => 'active',
                'name' => '林美華', 'email' => 'hua@mail.com', 'phone' => '0922-333-444',
                'password' => Hash::make('password123'),
                'id_number' => 'B234567890', 'birth_date' => '1988-09-22', 'gender' => 'female',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'type' => 'personal', 'status' => 'pending',
                'name' => '張志豪', 'email' => 'hao@mail.com', 'phone' => '0933-555-666',
                'password' => Hash::make('password123'),
                'id_number' => 'C345678901', 'birth_date' => '1995-03-10', 'gender' => 'male',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // 公司用戶測試資料
        Member::insert([
            [
                'type' => 'company', 'status' => 'active',
                'name' => '陳大文', 'email' => 'admin@techco.com', 'phone' => '02-1234-5678',
                'password' => Hash::make('password123'),
                'tax_id' => '12345678', 'company_name' => '科技股份有限公司',
                'website' => 'https://techco.com', 'industry' => 'tech',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'type' => 'company', 'status' => 'pending',
                'name' => '劉資訊', 'email' => 'info@infosoft.com', 'phone' => '02-8765-4321',
                'password' => Hash::make('password123'),
                'tax_id' => '87654321', 'company_name' => '資訊軟體有限公司',
                'website' => 'https://infosoft.com.tw', 'industry' => 'tech',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'type' => 'company', 'status' => 'active',
                'name' => '黃貿易', 'email' => 'biz@trade.com', 'phone' => '04-9876-5432',
                'password' => Hash::make('password123'),
                'tax_id' => '11223344', 'company_name' => '全球貿易企業社',
                'website' => 'https://globaltrade.tw', 'industry' => 'retail',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }
}
