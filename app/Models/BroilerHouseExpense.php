<?php

namespace App\Models;

use Database\Factories\BroilerHouseExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroilerHouseExpense extends Model
{
    /** @use HasFactory<BroilerHouseExpenseFactory> */
    use HasFactory;

    protected $fillable = ['description', 'amount', 'starts_on', 'ends_on'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(BroilerHouse::class, 'broiler_house_id');
    }
}
