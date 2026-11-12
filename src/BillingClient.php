<?php

declare(strict_types=1);

namespace IWD\BillingSdk;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Клиент универсального сервиса Billing (журнал покупок и расписание продления).
 *
 * Токен — персональный JWT системы (аудитория billing). Сервис берёт код системы из claim `sub`.
 * Деньги и доступ продукт проводит сам: сюда приходит уже совершённая покупка.
 * 404 у purchase() превращается в null. Остальные 4xx/5xx пробрасываются.
 */
final readonly class BillingClient
{
    public const int REFERENCE_MAX_LENGTH = 128;
    public const int ITEM_CODE_MAX_LENGTH = 64;
    public const int PAGE_SIZE_MAX = 100;
    public const string RENEWAL_FAILED_INSUFFICIENT_FUNDS = 'insufficient_funds';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $token,
    ) {
    }

    /**
     * Записать покупку. Повтор того же reference с теми же полями возвращает исходную запись.
     *
     * @param list<PurchaseLine> $lines
     */
    public function recordPurchase(
        int $userId,
        PurchaseItem $purchaseItem,
        string $purchaseItemCode,
        string $reference,
        string $amount,
        array $lines,
        ?string $accessId = null,
        ?DateTimeImmutable $dueAt = null,
        ?bool $autoRenewal = null,
        bool $renewal = false,
    ): Purchase {
        $this->assertReference($reference);
        $this->assertItemCode($purchaseItemCode);
        if ([] === $lines) {
            throw new InvalidArgumentException('Нужна хотя бы одна проводка Accounting.');
        }
        if (PurchaseItem::Tariff === $purchaseItem && (null === $accessId || null === $dueAt || null === $autoRenewal)) {
            throw new InvalidArgumentException('Для тарифа нужны accessId, dueAt и autoRenewal.');
        }

        return $this->mapPurchase($this->request('POST', '/purchases', [
            'json' => array_filter([
                'userId' => $userId,
                'purchaseItem' => $purchaseItem->value,
                'purchaseItemCode' => $purchaseItemCode,
                'reference' => $reference,
                'amount' => $amount,
                'accessId' => $accessId,
                'dueAt' => $dueAt?->format(DateTimeInterface::ATOM),
                'autoRenewal' => $autoRenewal,
                'renewal' => $renewal,
                'lines' => array_map(
                    static fn (PurchaseLine $line): array => [
                        'accountUuid' => $line->accountUuid,
                        'operationUuid' => $line->operationUuid,
                    ],
                    $lines,
                ),
            ], static fn (mixed $value): bool => null !== $value),
        ]));
    }

    /** Покупка по UUID. null — её нет или она принадлежит другой системе */
    public function purchase(string $uuid): ?Purchase
    {
        try {
            return $this->mapPurchase($this->request('GET', '/purchases/' . rawurlencode($uuid)));
        } catch (ClientExceptionInterface $exception) {
            if (404 === $exception->getResponse()->getStatusCode()) {
                return null;
            }

            throw $exception;
        }
    }

    public function purchases(?int $userId = null, int $page = 1, int $size = 20): PurchaseList
    {
        if ($page < 1 || $size < 1 || $size > self::PAGE_SIZE_MAX) {
            throw new InvalidArgumentException(sprintf('page ≥ 1, size от 1 до %d.', self::PAGE_SIZE_MAX));
        }
        $query = array_filter([
            'userId' => $userId,
            'page' => $page,
            'size' => $size,
        ], static fn (mixed $value): bool => null !== $value);
        $data = $this->request('GET', '/purchases', ['query' => $query]);

        return new PurchaseList(
            items: array_map($this->mapPurchase(...), array_values($data['items'])),
            totalCount: (int) $data['totalCount'],
        );
    }

    /** Отменить запись. Деньги продукт уже вернул в Accounting. Отдельного события нет */
    public function cancelPurchase(string $uuid): Purchase
    {
        return $this->mapPurchase($this->request('POST', '/purchases/' . rawurlencode($uuid) . '/cancel'));
    }

    /** Продление не удалось. Покупки нет. reason всегда insufficient_funds */
    public function failRenewal(string $accessId, int $userId): void
    {
        $this->request('POST', '/renewals/' . rawurlencode($accessId) . '/fail', [
            'json' => ['userId' => $userId],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $options['headers'] = ['Authorization' => 'Bearer ' . $this->token];
        $response = $this->httpClient->request($method, rtrim($this->baseUrl, '/') . '/api/sdk' . $path, $options);
        if (204 === $response->getStatusCode()) {
            return [];
        }

        return $response->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapPurchase(array $data): Purchase
    {
        $lines = [];
        foreach ($data['lines'] ?? [] as $line) {
            $lines[] = new PurchaseLine(
                accountUuid: (string) $line['accountUuid'],
                operationUuid: (string) $line['operationUuid'],
            );
        }

        return new Purchase(
            uuid: (string) $data['uuid'],
            userId: (int) $data['userId'],
            purchaseItem: PurchaseItem::from((string) $data['purchaseItem']),
            purchaseItemCode: (string) $data['purchaseItemCode'],
            reference: (string) $data['reference'],
            status: PurchaseStatus::from((string) $data['status']),
            amount: (string) $data['amount'],
            accessId: isset($data['accessId']) ? (string) $data['accessId'] : null,
            lines: $lines,
            createdAt: new DateTimeImmutable((string) $data['createdAt']),
            updatedAt: new DateTimeImmutable((string) $data['updatedAt']),
        );
    }

    private function assertReference(string $reference): void
    {
        if ('' === $reference || mb_strlen($reference) > self::REFERENCE_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('reference: непустая строка до %d символов.', self::REFERENCE_MAX_LENGTH));
        }
    }

    private function assertItemCode(string $code): void
    {
        if ('' === $code || mb_strlen($code) > self::ITEM_CODE_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('purchaseItemCode: непустая строка до %d символов.', self::ITEM_CODE_MAX_LENGTH));
        }
    }
}
