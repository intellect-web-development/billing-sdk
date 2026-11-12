# billing-sdk

HTTP-клиент и контракты шины универсального сервиса Billing (журнал покупок и расписание продления). Пакет `intellect-web-development/billing-sdk`, namespace `IWD\BillingSdk\`.

Чеклист подключения продукта: корневой `../billing/INTEGRATION.md`.

Сервис не списывает деньги и не выдаёт доступ. Продукт проводит Accounting и Tariffication своим токеном, затем записывает покупку сюда.

```php
$purchase = $client->recordPurchase(
    userId: 7,
    purchaseItem: PurchaseItem::Tariff,
    purchaseItemCode: 'pro',
    reference: 'order-1',
    amount: '100.00',
    lines: [new PurchaseLine($accountUuid, $operationUuid)],
    accessId: $accessId,
    dueAt: $dueAt,
    autoRenewal: true,
);
```

Токен — персональный JWT (аудитория `billing`, scopes `billing:purchases:read` и `billing:purchases:write`).

События в `billing.events`: `billing.event.purchaseRecorded`, `billing.event.renewalDue`, `billing.event.renewalSucceeded`, `billing.event.renewalFailed`. Классы — `IWD\BillingSdk\Message\Event\*`. Очередь продукта — `examples/symfony/messenger.yaml`.
