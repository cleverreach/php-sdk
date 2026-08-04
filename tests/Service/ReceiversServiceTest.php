<?php

declare(strict_types=1);

namespace CleverReach\Tests\Service;

use CleverReach\SDK\Exception\ResourceNotFoundException;
use CleverReach\SDK\Http\ApiRequestorInterface;
use CleverReach\SDK\Model\ReceiverModel;
use CleverReach\SDK\Service\ReceiversService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ReceiversService::class)]
final class ReceiversServiceTest extends TestCase
{
    private const RECEIVER_ID = 21;
    private const GROUP_ID = 42;
    private ApiRequestorInterface&MockObject $requestor;
    private ReceiversService $service;

    protected function setUp(): void {
        $this->requestor = $this->createMock(ApiRequestorInterface::class);
        $this->service = new ReceiversService($this->requestor);
    }

    public function testGetByNumericIdBuildsCorrectEndpoint(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'receivers/'.self::RECEIVER_ID, ['group_id' => null])
            ->willReturn(['id' => self::RECEIVER_ID, 'email' => 'jane@example.com'])
        ;

        $receiver = $this->service->get(self::RECEIVER_ID);

        self::assertInstanceOf(ReceiverModel::class, $receiver);
        self::assertSame('jane@example.com', $receiver->email);
    }

    public function testGetByEmailBuildsCorrectEndpoint(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'receivers/jane@example.com', ['group_id' => null])
            ->willReturn(['id' => self::RECEIVER_ID, 'email' => 'jane@example.com'])
        ;

        $receiver = $this->service->get('jane@example.com');
        self::assertSame('jane@example.com', $receiver->email);
    }

    public function testGetPassesGroupIdAsQueryParameter(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'receivers/'.self::RECEIVER_ID, ['group_id' => self::GROUP_ID])
            ->willReturn(['id' => self::RECEIVER_ID, 'email' => 'jane@example.com'])
        ;

        $this->service->get(self::RECEIVER_ID, self::GROUP_ID);
    }

    public function testGetUnwrapsListResponseAndReturnsFirstElement(): void {
        $this->requestor
            ->method('request')
            ->willReturn([
                ['id' => self::RECEIVER_ID, 'email' => 'first@example.com'],
                ['id' => 2, 'email' => 'second@example.com'],
            ])
        ;

        $receiver = $this->service->get(self::RECEIVER_ID);
        self::assertSame('first@example.com', $receiver->email);
    }

    public function testGetThrowsExceptionWhenListIsEmpty(): void {
        $this->requestor
            ->method('request')
            ->willReturn([]) // API returns empty list
        ;

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("Receiver '999' not found.");

        $this->service->get(999);
    }
}
