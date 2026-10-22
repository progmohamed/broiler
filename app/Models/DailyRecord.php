<?php

namespace App\Models;

use Database\Factories\DailyRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRecord extends Model
{
    public const FEED_SACK_WEIGHT_KG = 50;

    /** @use HasFactory<DailyRecordFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'recorded_on',
        'production',
        'feed',
        'deaths',
        'medicines',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'production' => 'integer',
            'feed' => 'integer',
            'deaths' => 'integer',
        ];
    }

    public function feedInSacks(): float
    {
        return $this->feed / self::FEED_SACK_WEIGHT_KG;
    }

    public function productionInCartons(): float
    {
        return $this->production / EggSale::EGGS_PER_CARTON;
    }
}
