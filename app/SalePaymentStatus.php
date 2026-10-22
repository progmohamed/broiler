<?php

namespace App;

enum SalePaymentStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'مدفوع',
            self::Pending => 'آجل',
        };
    }
}
