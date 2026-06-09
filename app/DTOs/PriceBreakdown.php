<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class PriceBreakdown
{
    public function __construct(
        public readonly float $basePrice,
        public readonly int $nights,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $loyaltyDiscount,
        public readonly float $serviceFee,
        public readonly float $total,
        public readonly ?string $promotionLabel = null,
        public readonly ?string $couponCode = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            basePrice: (float) ($data['base_price'] ?? 0),
            nights: (int) ($data['nights'] ?? 1),
            subtotal: (float) ($data['subtotal'] ?? 0),
            discount: (float) ($data['discount'] ?? 0),
            loyaltyDiscount: (float) ($data['loyalty_discount'] ?? 0),
            serviceFee: (float) ($data['service_fee'] ?? 0),
            total: (float) ($data['total'] ?? 0),
            promotionLabel: $data['promotion_label'] ?? null,
            couponCode: $data['coupon_code'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'base_price'       => $this->basePrice,
            'nights'           => $this->nights,
            'subtotal'         => $this->subtotal,
            'discount'         => $this->discount,
            'loyalty_discount' => $this->loyaltyDiscount,
            'service_fee'      => $this->serviceFee,
            'total'            => $this->total,
            'promotion_label'  => $this->promotionLabel,
            'coupon_code'      => $this->couponCode,
        ];
    }

    public function totalDiscount(): float
    {
        return $this->discount + $this->loyaltyDiscount;
    }

    public function discountPercent(): int
    {
        if ($this->subtotal <= 0) {
            return 0;
        }

        return (int) round(($this->totalDiscount() / $this->subtotal) * 100);
    }
}
