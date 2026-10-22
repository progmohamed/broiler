<?php

namespace App\Models;

use Database\Factories\BroilerCycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroilerCycle extends Model
{
    /** @use HasFactory<BroilerCycleFactory> */
    use HasFactory;

    protected $fillable = ['broiler_house_id', 'name', 'started_on', 'initial_birds', 'initial_weight_g', 'chick_cost', 'closed_on'];

    protected function casts(): array
    {
        return ['started_on' => 'date', 'closed_on' => 'date', 'initial_birds' => 'integer', 'initial_weight_g' => 'decimal:2', 'chick_cost' => 'decimal:2'];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(BroilerHouse::class, 'broiler_house_id');
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(BroilerVaccination::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BroilerEntry::class);
    }
}
