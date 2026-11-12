<?php

declare(strict_types=1);

namespace IWD\BillingSdk\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use IWD\BillingSdk\BillingClient;
use IWD\BillingSdk\PurchaseItem;
use IWD\BillingSdk\PurchaseLine;
use IWD\BillingSdk\PurchaseStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BillingClientTest extends TestCase
{
    private const string PURCHASE = '{"uuid":"019c4000-0000-7000-8000-000000000001","userId":7,"purchaseItem":"tariff","purchaseItemCode":"pro","reference":"ref-1","status":"recorded","amount":"100.00","accessId":"access-1","lines":[{"accountUuid":"01936a8e-8c2a-7c4e-9b1a-2d3e4f506172","operationUuid":"01936a8e-8c2a-7c4e-9b1a-2d3e4f506173"}],"createdAt":"2026-11-12T10:00:00+00:00","updatedAt":"2026-11-12T10:00:00+00:00"}';

    /** @var array{method?: string, url?: string, body?: string} */
    private array $captured = [];

    public function testRecordPurchasePostsTariffFields(): void
    {
        $purchase = $this->client(self::PURCHASE, 201)->recordPurchase(
            userId: 7,
            purchaseItem: PurchaseItem::Tariff,
            purchaseItemCode: 'pro',
            reference: 'ref-1',
            amount: '100.00',
            lines: [new PurchaseLine('01936a8e-8c2a-7c4e-9b1a-2d3e4f506172', '01936a8e-8c2a-7c4e-9b1a-2d3e4f506173')],
            accessId: 'access-1',
            dueAt: new DateTimeImmutable('2026-12-12T10:00:00+00:00'),
            autoRenewal: true,
        );

        self::assertSame('POST', $this->captured['method']);
        self::assertSame('http://billing.test/api/sdk/purchases', $this->captured['url']);
        self::assertSame(PurchaseStatus::Recorded, $purchase->status);
        self::assertSame('access-1', $purchase->accessId);
        self::assertCount(1, $purchase->lines);
    }

    public function testPurchaseReturnsNullOnNotFound(): void
    {
        self::assertNull($this->client('{"message":"not found"}', 404)->purchase('019c4000-0000-7000-8000-000000000009'));
    }

    public function testFailRenewalPostsUserId(): void
    {
        $this->client('', 204)->failRenewal('access-1', 7);

        self::assertSame('POST', $this->captured['method']);
        self::assertSame('http://billing.test/api/sdk/renewals/access-1/fail', $this->captured['url']);
        self::assertSame(['userId' => 7], json_decode((string) $this->captured['body'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testTariffWithoutScheduleIsRejectedBeforeRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->client(self::PURCHASE)->recordPurchase(
            userId: 7,
            purchaseItem: PurchaseItem::Tariff,
            purchaseItemCode: 'pro',
            reference: 'ref-1',
            amount: '100.00',
            lines: [new PurchaseLine('a', 'b')],
        );
    }

    private function client(string $body, int $status = 200): BillingClient
    {
        $this->captured = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($body, $status): MockResponse {
            $this->captured = [
                'method' => $method,
                'url' => $url,
                'body' => (string) ($options['body'] ?? ''),
            ];

            return new MockResponse($body, ['http_code' => $status, 'response_headers' => ['content-type: application/json']]);
        });

        return new BillingClient($httpClient, 'http://billing.test', 'jwt-token');
    }
}
