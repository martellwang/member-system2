<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'type', 'name', 'email', 'phone', 'password', 'status',
        // 個人
        'id_number', 'birth_date', 'gender',
        // 公司
        'tax_id', 'company_name', 'website', 'industry',
        // 驗證
        'email_verified_token', 'email_verified_at',
    ];

    protected $hidden = [
        'password', 'email_verified_token',
    ];

    protected $casts = [
        'birth_date'        => 'date',
        'email_verified_at' => 'datetime',
    ];

    // Scopes
    public function scopePersonal($query)  { return $query->where('type', 'personal'); }
    public function scopeCompany($query)   { return $query->where('type', 'company'); }
    public function scopeActive($query)    { return $query->where('status', 'active'); }
    public function scopePending($query)   { return $query->where('status', 'pending'); }

    // Helpers
    public function isPersonal(): bool { return $this->type === 'personal'; }
    public function isCompany(): bool  { return $this->type === 'company'; }
    public function isActive(): bool   { return $this->status === 'active'; }
}
