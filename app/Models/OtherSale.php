<?php

namespace App\Models;

use App\SalePaymentStatus;
use App\SaleProductType;
use App\SaleUnit;
use Database\Factories\OtherSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherSale extends Model
{
    /** @use HasFactory<OtherSaleFactory> */
    use HasFactory;

    protected $fillable = [
        'sold_on',
        'product_type',
        'custom_product',
        'quantity',
        'unit',
        'unit_price',
        'buyer',
        'payment_status',
        'invoice_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'product_type' => SaleProductType::class,
            'quantity' => 'decimal:3',
            'unit' => SaleUnit::class,
            'unit_price' => 'decimal:2',
            'payment_status' => SalePaymentStatus::class,
        ];
    }

    public function productLabel(): string
    {
        if ($this->product_type === SaleProductType::Other && $this->custom_product !== null) {
            return $this->custom_product;
        }

        return $this->product_type->label();
    }

    public function totalAmount(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
