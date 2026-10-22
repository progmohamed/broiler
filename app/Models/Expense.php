<?php

namespace App\Models;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'spent_on',
        'category',
        'amount',
        'payment_method',
        'payee',
        'invoice_number',
        'notes',
    ];

    public function feedDelivery(): BelongsTo
    {
        return $this->belongsTo(FeedDelivery::class);
    }

    public function workerAdvance(): BelongsTo
    {
        return $this->belongsTo(WorkerAdvance::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'payment_method' => ExpensePaymentMethod::class,
        ];
    }
}
