<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admin extends Model
{
    use HasFactory;

    protected $table = 'admins';
    protected $primaryKey = 'admin_id';

    public $timestamps = false;

    protected $fillable = [
        'admin_name',
        'phone_number',
        'last_login',
        'status',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
        ];
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class, 'admin_id', 'admin_id');
    }
}
