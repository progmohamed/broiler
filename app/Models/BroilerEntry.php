<?php

namespace App\Models;

use Database\Factories\BroilerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroilerEntry extends Model
{
    public const SACK_WEIGHT_KG = 50;

    public const TYPES = ['feed_delivery' => 'نقلة علف', 'daily' => 'يومية', 'weight' => 'وزن عينة', 'medicine' => 'دواء', 'sale' => 'بيع طيور', 'expense' => 'مصروف'];

    public const PAYMENT_STATUSES = ['cash' => 'كاش', 'credit' => 'آجل'];

    public const CHICK_COST_CATEGORIES = ['heating' => 'التدفئة', 'bedding' => 'الفرشة', 'salaries' => 'العمالة', 'miscellaneous' => 'النثريات'];

    public const EXPENSE_CATEGORIES = ['disinfection' => 'تطهير', 'heating' => 'تدفئة', 'bedding' => 'نشارة (فرشة)', 'miscellaneous' => 'نثريات', 'salaries' => 'مرتبات', 'electricity' => 'كهرباء (فواتير وبنزين)', 'maintenance' => 'صيانات', 'medicines' => 'أدوية', 'other' => 'أخرى'];

    /** @use HasFactory<BroilerEntryFactory> */
    use HasFactory;

    protected $fillable = ['kind', 'recorded_on', 'daily_on', 'birds', 'feed_kg', 'weight_kg', 'unit_price', 'amount', 'paid', 'description', 'notes', 'medicines', 'category', 'feed_type', 'supplier', 'invoice_number', 'shipping_cost', 'price_per_ton', 'payment_status'];

    protected function casts(): array
    {
        return ['price_per_ton' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'recorded_on' => 'date', 'daily_on' => 'date', 'birds' => 'integer', 'feed_kg' => 'decimal:3', 'weight_kg' => 'decimal:3', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2', 'paid' => 'decimal:2'];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BroilerCycle::class, 'broiler_cycle_id');
    }
}
