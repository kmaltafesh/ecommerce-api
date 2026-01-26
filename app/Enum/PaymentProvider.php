<?php

namespace App\Enum;

enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case CASH_ON_DELIVERY = 'cash_on_delivery';
    case BANK_TRANSFER = 'bank_transfer';

    // ميزة إضافية: الحصول على أسماء العرض (Label) باللغة العربية مثلاً
    public function label(): string
    {
        return match($this) {
            self::STRIPE => 'الدفع عبر سترايب',
            self::PAYPAL => 'بايبال',
            self::CASH_ON_DELIVERY => 'الدفع عند الاستلام',
            self::BANK_TRANSFER => 'تحويل بنكي',
        };
    }
}