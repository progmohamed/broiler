<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmSetting extends Model
{
    protected $hidden = ['app_pin'];

    /** @var list<string> */
    protected $fillable = [
        'farm_name',
        'herd_birth_date',
        'total_herd_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'herd_birth_date' => 'date',
            'total_herd_count' => 'integer',
        ];
    }
}
