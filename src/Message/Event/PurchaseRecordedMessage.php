<?php

declare(strict_types=1);

namespace IWD\BillingSdk\Message\Event;

use Symfony\Component\Messenger\Attribute\AsMessage;

/** Покупка записана. Данные получатель берёт по BillingClient::purchase() */
#[AsMessage(transport: 'billing-events-transport', serializedTypeName: 'billing.event.purchaseRecorded')]
readonly class PurchaseRecordedMessage
{
    public function __construct(
        public string $purchaseUuid,
        public string $integrationSystemCode,
    ) {
    }
}
