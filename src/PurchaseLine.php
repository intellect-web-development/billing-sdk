<?php

declare(strict_types=1);

namespace IWD\BillingSdk;

/** Ссылки на счёт и операцию Accounting. Billing их не интерпретирует */
final readonly class PurchaseLine
{
    public function __construct(
        public string $accountUuid,
        public string $operationUuid,
    ) {
    }
}
