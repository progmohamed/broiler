<?php

namespace App\Models;

use Database\Factories\BroilerHouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroilerHouse extends Model
{
    /** @use HasFactory<BroilerHouseFactory> */
    use HasFactory;

    protected $fillable = ['name', 'capacity'];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(BroilerHouseExpense::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(BroilerCycle::class);
    }
}
