<?php

namespace App\Models;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use Database\Factories\WorkerAdvanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkerAdvance extends Model
{
    /** @use HasFactory<WorkerAdvanceFactory> */
    use HasFactory;

    protected $fillable = [
        'advanced_on',
        'worker_name',
        'amount',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'advanced_on' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => ExpensePaymentMethod::class,
        ];
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function syncExpense(): Expense
    {
        return $this->expense()->updateOrCreate([], [
            'spent_on' => $this->advanced_on,
            'category' => ExpenseCategory::WorkerAdvance,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payee' => $this->worker_name,
            'invoice_number' => null,
            'notes' => $this->notes,
        ]);
    }
}
