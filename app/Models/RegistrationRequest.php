<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistrationRequest extends Model
{
    use HasFactory;

    protected $table = 'registration_requests';

    protected $primaryKey = 'registration_id';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'status',
        'token',
        'expires_at',
        'approved_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
