<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $primaryKey = 'room_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'room_number',
        'building_number',
        'building_address',
        'gender_allowed',
        'tenants_count',
        'maximum_tenants',
        'rent_price',
        'availability_status',
    ];

    protected function casts(): array
    {
        return [
            'tenants_count' => 'integer',
            'maximum_tenants' => 'integer',
            'rent_price' => 'decimal:2',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(
            Tenant::class,
            'room_id',
            'room_id'
        );
    }
}
