<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['personal', 'company'])->comment('個人用戶 / 商業公司');

            // 共同欄位
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'pending'])->default('pending');

            // 個人用戶欄位
            $table->string('id_number', 10)->nullable()->comment('身分證號');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // 商業公司欄位
            $table->string('tax_id', 8)->nullable()->comment('統一編號');
            $table->string('company_name')->nullable()->comment('公司名稱');
            $table->string('website')->nullable()->comment('公司網站網址');
            $table->string('industry')->nullable()->comment('產業類別');

            $table->string('email_verified_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
