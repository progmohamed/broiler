<?php

namespace App\Models;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use Database\Factories\FeedDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FeedDelivery extends Model
{
    /** @use HasFactory<FeedDeliveryFactory> */
    use HasFactory;

    public const KILOGRAMS_PER_TON = 1000;

    public const SACK_WEIGHT_KILOGRAMS = 50;

    /** @var list<string> */
    protected $fillable = [
        'delivered_on',
        'feed_type',
        'weight_tons',
        'price_per_ton',
        'shipping_cost',
        'tip',
        'supplier',
        'payment_method',
        'invoice_number',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'delivered_on' => 'date',
            'weight_tons' => 'decimal:3',
            'price_per_ton' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tip' => 'decimal:2',
            'payment_method' => ExpensePaymentMethod::class,
        ];
    }

    public function weightInKilograms(): float
    {
        return (float) $this->weight_tons * self::KILOGRAMS_PER_TON;
    }

    public function sackCount(): float
    {
        return $this->weightInKilograms() / self::SACK_WEIGHT_KILOGRAMS;
    }

    public function totalCost(): float
    {
        return ((float) $this->weight_tons * (float) $this->price_per_ton)
            + (float) $this->shipping_cost
            + (float) $this->tip;
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function syncExpense(): Expense
    {
        $notes = 'توريد '.$this->feed_type;

        if ($this->notes !== null) {
            $notes .= "\n".$this->notes;
        }

        return $this->expense()->updateOrCreate([], [
            'spent_on' => $this->delivered_on,
            'category' => ExpenseCategory::Feed,
            'amount' => $this->totalCost(),
            'payment_method' => $this->payment_method,
            'payee' => $this->supplier,
            'invoice_number' => $this->invoice_number,
            'notes' => $notes,
        ]);
    }
}
