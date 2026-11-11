<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Tenant extends Model
{
    use HasFactory;

    protected $table = 'tenants';
    protected $primaryKey = 'tenant_id';

    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;
    public function billings(): HasMany
    {
        return $this->hasMany(
            Billing::class,
            'tenant_id',
            'tenant_id'
        );
    }
    protected $fillable = [
        'tenant_name',
        'tenant_age',
        'tenant_address',
        'tenant_number',
        'room_id',
        'tenant_gender',
        'move_in_date',
        'status',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'tenant_age' => 'integer',
            'move_in_date' => 'date',
        ];
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class, 'tenant_id', 'tenant_id');
    }
}
