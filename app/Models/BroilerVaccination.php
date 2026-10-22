<?php

namespace App\Models;

use Database\Factories\BroilerVaccinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroilerVaccination extends Model
{
    public const TIMEZONE = 'Africa/Cairo';

    /** @use HasFactory<BroilerVaccinationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'due_at', 'timezone', 'remind_before_hours', 'remind_at', 'notes', 'completed_at'];

    protected function casts(): array
    {
        return ['due_at' => 'immutable_datetime', 'remind_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'remind_before_hours' => 'integer'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BroilerCycle::class, 'broiler_cycle_id');
    }

    public function statusLabel(): string
    {
        if ($this->completed_at) {
            return 'تم التحصين';
        }
        if ($this->due_at->isPast()) {
            return 'متأخر';
        }

        return $this->remind_at->isPast() ? 'اقترب الموعد' : 'قادم';
    }
}
