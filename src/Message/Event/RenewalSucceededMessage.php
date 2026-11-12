<?php

declare(strict_types=1);

namespace IWD\BillingSdk\Message\Event;

use Symfony\Component\Messenger\Attribute\AsMessage;

/** Продление записано отдельной покупкой. Проекция кабинета берёт её по purchaseUuid */
#[AsMessage(transport: 'billing-events-transport', serializedTypeName: 'billing.event.renewalSucceeded')]
readonly class RenewalSucceededMessage
{
    public function __construct(
        public string $purchaseUuid,
        public string $integrationSystemCode,
    ) {
    }
}
