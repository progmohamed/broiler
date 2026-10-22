<?php

namespace App\Models;

use Database\Factories\EggSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EggSale extends Model
{
    /** @use HasFactory<EggSaleFactory> */
    use HasFactory;

    public const EGGS_PER_CARTON = 30;

    /** @var list<string> */
    protected $fillable = [
        'sold_on',
        'cartons',
        'price_per_carton',
        'buyer',
        'payment_status',
        'invoice_number',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'cartons' => 'integer',
            'price_per_carton' => 'decimal:2',
        ];
    }

    public function totalAmount(): float
    {
        return $this->cartons * (float) $this->price_per_carton;
    }
}
