<?php

declare(strict_types=1);

namespace IWD\BillingSdk\Message\Event;

use Symfony\Component\Messenger\Attribute\AsMessage;

/** Пора продлить доступ. Продукт списывает деньги и подтверждает покупку по HTTP */
#[AsMessage(transport: 'billing-events-transport', serializedTypeName: 'billing.event.renewalDue')]
readonly class RenewalDueMessage
{
    public function __construct(
        public int $userId,
        public string $accessId,
        public string $integrationSystemCode,
    ) {
    }
}
