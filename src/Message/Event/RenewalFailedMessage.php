<?php

declare(strict_types=1);

namespace IWD\BillingSdk\Message\Event;

use Symfony\Component\Messenger\Attribute\AsMessage;

/** Продление не удалось, покупки нет. reason = insufficient_funds */
#[AsMessage(transport: 'billing-events-transport', serializedTypeName: 'billing.event.renewalFailed')]
readonly class RenewalFailedMessage
{
    public function __construct(
        public int $userId,
        public string $accessId,
        public string $reason,
        public string $integrationSystemCode,
    ) {
    }
}
