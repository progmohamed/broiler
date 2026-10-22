<?php

namespace App;

enum ExpensePaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::BankTransfer => 'تحويل بنكي',
            self::Credit => 'آجل',
        };
    }
}
