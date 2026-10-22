<?php

namespace App;

enum SaleProductType: string
{
    case EmptySacks = 'empty_sacks';
    case Manure = 'manure';
    case Broilers = 'broilers';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EmptySacks => 'شكاير فارغة',
            self::Manure => 'سبلة',
            self::Broilers => 'فراخ لحم',
            self::Other => 'بيع آخر',
        };
    }
}
