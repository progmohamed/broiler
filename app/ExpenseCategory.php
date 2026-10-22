<?php

namespace App;

enum ExpenseCategory: string
{
    case Feed = 'feed';
    case Equipment = 'equipment';
    case Maintenance = 'maintenance';
    case Electricity = 'electricity';
    case Water = 'water';
    case Fuel = 'fuel';
    case Rent = 'rent';
    case Salary = 'salary';
    case WorkerAdvance = 'worker_advance';
    case WorkerFood = 'worker_food';
    case Bonus = 'bonus';
    case Medicines = 'medicines';
    case Transportation = 'transportation';
    case Cleaning = 'cleaning';
    case PettyCash = 'petty_cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Feed => 'شراء علف',
            self::Equipment => 'معدات',
            self::Maintenance => 'صيانة',
            self::Electricity => 'كهرباء',
            self::Water => 'مياه',
            self::Fuel => 'وقود',
            self::Rent => 'إيجار',
            self::Salary => 'راتب',
            self::WorkerAdvance => 'سلفة عامل',
            self::WorkerFood => 'طعام العمال',
            self::Bonus => 'مكافأة',
            self::Medicines => 'أدوية',
            self::Transportation => 'نقل',
            self::Cleaning => 'أدوات نظافة',
            self::PettyCash => 'مصروفات نثرية',
            self::Other => 'أخرى',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Feed => 'التشغيل',
            self::Equipment, self::Maintenance, self::Electricity, self::Water, self::Fuel, self::Rent => 'المزرعة',
            self::Salary, self::WorkerAdvance, self::WorkerFood, self::Bonus => 'العمال',
            self::Medicines, self::Transportation, self::Cleaning => 'التشغيل',
            self::PettyCash, self::Other => 'أخرى',
        };
    }
}
