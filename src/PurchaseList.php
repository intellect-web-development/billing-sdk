<?php

declare(strict_types=1);

namespace IWD\BillingSdk;

final readonly class PurchaseList
{
    /**
     * @param list<Purchase> $items
     */
    public function __construct(
        public array $items,
        public int $totalCount,
    ) {
    }
}
