<?php

declare(strict_types=1);

namespace CleverReach\Tests\Service;

use CleverReach\SDK\Collection\GroupCollection;
use CleverReach\SDK\Collection\ReceiverCollection;
use CleverReach\SDK\Enum\GroupSortField;
use CleverReach\SDK\Enum\ReceiverType;
use CleverReach\SDK\Enum\SortOrder;
use CleverReach\SDK\Exception\ResourceNotFoundException;
use CleverReach\SDK\Http\ApiRequestorInterface;
use CleverReach\SDK\Model\GroupModel;
use CleverReach\SDK\Service\GroupsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GroupsService::class)]
final class GroupsServiceTest extends TestCase
{
    private const GROUP_ID = 21;
    private ApiRequestorInterface&MockObject $requestor;
    private GroupsService $service;

    protected function setUp(): void {
        $this->requestor = $this->createMock(ApiRequestorInterface::class);
        $this->service = new GroupsService($this->requestor);
    }

    public function testGetBuildsCorrectEndpoint(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID)
            ->willReturn(['id' => self::GROUP_ID, 'name' => 'Newsletter List'])
        ;

        $group = $this->service->get(self::GROUP_ID);

        self::assertInstanceOf(GroupModel::class, $group);
        self::assertSame('Newsletter List', $group->name);
    }

    public function testGetUnwrapsListResponseAndReturnsFirstElement(): void {
        $this->requestor
            ->method('request')
            ->willReturn([
                ['id' => self::GROUP_ID, 'name' => 'First List'],
                ['id' => 22, 'name' => 'Second List'],
            ])
        ;

        $group = $this->service->get(self::GROUP_ID);
        self::assertSame('First List', $group->name);
    }

    public function testGetThrowsExceptionIfListIsEmpty(): void {
        $this->requestor
            ->method('request')
            ->willReturn([]) // API returns empty array for not found group sometimes
        ;

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("Group '555' not found.");

        $this->service->get(555);
    }

    public function testAllBuildsCorrectEndpointWithDefaults(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups', ['order' => null])
            ->willReturn([
                ['id' => 1, 'name' => 'First List'],
                ['id' => 2, 'name' => 'Second List'],
            ])
        ;

        $groups = $this->service->all();

        self::assertInstanceOf(GroupCollection::class, $groups);
        self::assertCount(2, $groups);
        self::assertSame('First List', iterator_to_array($groups)[0]->name);
    }

    public function testAllBuildsCorrectEndpointWithSorting(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups', ['order' => 'created DESC'])
            ->willReturn([])
        ;

        $this->service->all(GroupSortField::Created, SortOrder::Descending);
    }

    public function testAllReturnsEmptyCollectionWhenResponseIsNotAList(): void {
        $this->requestor
            ->method('request')
            ->willReturn(['error' => 'something went wrong'])
        ;

        $groups = $this->service->all();
        self::assertCount(0, $groups);
    }

    public function testGetReceiversBuildsCorrectQueryWithDefaults(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'groups/'.self::GROUP_ID.'/receivers',
                [
                    'page' => 0,
                    'pagesize' => 50,
                    'type' => null,
                    'detail' => null,
                    'email_list' => null,
                    'id_list' => null,
                    'order_by' => null,
                ]
            )
            ->willReturn([
                ['id' => 100, 'email' => 'jane@example.com'],
            ])
        ;

        $receivers = $this->service->getReceivers(self::GROUP_ID);

        self::assertInstanceOf(ReceiverCollection::class, $receivers);
        self::assertCount(1, $receivers);
        self::assertSame('jane@example.com', iterator_to_array($receivers)[0]->email);
    }

    public function testGetReceiversBuildsCorrectQueryWithAllFilters(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'groups/'.self::GROUP_ID.'/receivers',
                [
                    'page' => 2,
                    'pagesize' => 10,
                    'type' => 'active',
                    'detail' => 7,
                    'email_list' => 'a@test.com,b@test.com',
                    'id_list' => '1,2,3',
                    'order_by' => 'email ASC',
                ]
            )
            ->willReturn([])
        ;

        $this->service->getReceivers(
            self::GROUP_ID,
            2,
            10,
            ReceiverType::Active,
            7,
            ['a@test.com', 'b@test.com'],
            ['1', '2', '3'],
            'email',
            SortOrder::Ascending
        );
    }

    public function testGetReceiversReturnsEmptyCollectionWhenResponseIsNotAList(): void {
        $this->requestor
            ->method('request')
            ->willReturn(['error' => 'no connection'])
        ;

        $receivers = $this->service->getReceivers(self::GROUP_ID);
        self::assertCount(0, $receivers);
    }
}
