<?php

declare(strict_types=1);

namespace App\Common\Billing;

use IWD\BillingSdk\BillingClient;
use IWD\BillingSdk\Purchase;
use IWD\BillingSdk\PurchaseItem;
use IWD\BillingSdk\PurchaseLine;

/**
 * Пример шлюза. Домен продукта импортирует этот класс, не SDK.
 * Списание Accounting и выдача доступа Tariffication остаются в продукте и идут до recordPurchase.
 */
final readonly class BillingGateway
{
    public function __construct(private BillingClient $billingClient)
    {
    }

    /**
     * @param list<PurchaseLine> $lines
     */
    public function recordTariff(
        int $userId,
        string $tariffCode,
        string $reference,
        string $amount,
        array $lines,
        string $accessId,
        \DateTimeImmutable $dueAt,
        bool $autoRenewal,
        bool $renewal = false,
    ): Purchase {
        return $this->billingClient->recordPurchase(
            userId: $userId,
            purchaseItem: PurchaseItem::Tariff,
            purchaseItemCode: $tariffCode,
            reference: $reference,
            amount: $amount,
            lines: $lines,
            accessId: $accessId,
            dueAt: $dueAt,
            autoRenewal: $autoRenewal,
            renewal: $renewal,
        );
    }
}
