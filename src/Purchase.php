<?php

declare(strict_types=1);

namespace IWD\BillingSdk;

use DateTimeImmutable;

final readonly class Purchase
{
    /**
     * @param list<PurchaseLine> $lines
     */
    public function __construct(
        public string $uuid,
        public int $userId,
        public PurchaseItem $purchaseItem,
        public string $purchaseItemCode,
        public string $reference,
        public PurchaseStatus $status,
        public string $amount,
        public ?string $accessId,
        public array $lines,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
