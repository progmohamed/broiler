<?php

namespace App;

enum SaleUnit: string
{
    case Sack = 'sack';
    case Ton = 'ton';
    case CubicMeter = 'cubic_meter';
    case Load = 'load';
    case Bird = 'bird';
    case Kilogram = 'kilogram';
    case Unit = 'unit';

    public function label(): string
    {
        return match ($this) {
            self::Sack => 'شيكارة',
            self::Ton => 'طن',
            self::CubicMeter => 'متر مكعب',
            self::Load => 'نقلة',
            self::Bird => 'فرخة',
            self::Kilogram => 'كيلوجرام',
            self::Unit => 'وحدة',
        };
    }
}
