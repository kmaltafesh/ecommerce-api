<?php

namespace App\Enum;

enum Orderstatus: string
{
    //
    case PENDING = 'pending';
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getAllowedTransitions(): array{
        return match($this){
            self::PENDING=>[self::PAID, self::CANCELLED],
            self::PAID=>[self::PROCESSING, self::CANCELLED],
            self::PROCESSING=>[self::SHIPPED, self::CANCELLED],
            self::DELIVERED=>[],
            self::CANCELLED=>[]
        };

    }
    public function canTranstionTo(Orderstatus $targetStatus){
        return in_array($targetStatus,$this->getAllowedTransitions());
    }
}
