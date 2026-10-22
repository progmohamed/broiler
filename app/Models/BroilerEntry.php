<?php

namespace App\Models;

use Database\Factories\BroilerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroilerEntry extends Model
{
    public const TYPES = ['daily' => 'يومية', 'weight' => 'وزن عينة', 'medicine' => 'دواء', 'sale' => 'بيع طيور', 'expense' => 'مصروف'];

    /** @use HasFactory<BroilerEntryFactory> */
    use HasFactory;

    protected $fillable = ['kind', 'recorded_on', 'daily_on', 'birds', 'feed_kg', 'weight_kg', 'unit_price', 'amount', 'paid', 'description', 'notes'];

    protected function casts(): array
    {
        return ['recorded_on' => 'date', 'daily_on' => 'date', 'birds' => 'integer', 'feed_kg' => 'decimal:3', 'weight_kg' => 'decimal:3', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2', 'paid' => 'decimal:2'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BroilerCycle::class, 'broiler_cycle_id');
    }
}
