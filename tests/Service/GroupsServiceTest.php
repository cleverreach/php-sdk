<?php

declare(strict_types=1);

namespace CleverReach\Tests\Service;

use CleverReach\SDK\Collection\GroupCollection;
use CleverReach\SDK\Collection\ReceiverCollection;
use CleverReach\SDK\Model\GroupModel;
use CleverReach\SDK\Enum\GroupSortField;
use CleverReach\SDK\Enum\ReceiverType;
use CleverReach\SDK\Enum\SortOrder;
use CleverReach\SDK\Http\ApiRequestorInterface;
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
    private const GROUP_ID = 42;

    private ApiRequestorInterface&MockObject $requestor;
    private GroupsService $service;

    protected function setUp(): void {
        $this->requestor = $this->createMock(ApiRequestorInterface::class);
        $this->service = new GroupsService($this->requestor);
    }

    // -------------------------------------------------------------------------
    // get()
    // -------------------------------------------------------------------------

    public function testGetReturnsGroupForAssociativeResponse(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID, [])
            ->willReturn(['id' => self::GROUP_ID, 'name' => 'Newsletter', 'locked' => false, 'backup' => true])
        ;

        $group = $this->service->get(self::GROUP_ID);

        self::assertInstanceOf(GroupModel::class, $group);
        self::assertSame(self::GROUP_ID, $group->id);
        self::assertSame('Newsletter', $group->name);
    }

    public function testGetUnwrapsListResponseAndReturnsFirstElement(): void {
        $this->requestor
            ->method('request')
            ->willReturn([['id' => 1, 'name' => 'Wrapped']])
        ;

        $group = $this->service->get(1);

        self::assertSame(1, $group->id);
        self::assertSame('Wrapped', $group->name);
    }

    // -------------------------------------------------------------------------
    // all()
    // -------------------------------------------------------------------------

    public function testAllReturnsGroupCollectionWithoutSorting(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups', ['order' => null])
            ->willReturn([
                ['id' => 1, 'name' => 'Alpha'],
                ['id' => 2, 'name' => 'Beta'],
            ])
        ;

        $collection = $this->service->all();

        self::assertInstanceOf(GroupCollection::class, $collection);
        self::assertCount(2, $collection);
    }

    public function testAllPassesSortOrderToRequest(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups', ['order' => 'changed DESC'])
            ->willReturn([])
        ;

        $this->service->all(GroupSortField::Changed, SortOrder::Descending);
    }

    public function testAllPassesSortFieldWithoutDirectionTrimsTrailingSpace(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups', ['order' => 'created'])
            ->willReturn([])
        ;

        $this->service->all(GroupSortField::Created);
    }

    public function testAllReturnsEmptyCollectionWhenResponseIsNotAList(): void {
        $this->requestor
            ->method('request')
            ->willReturn(['id' => 1, 'name' => 'Single object, not a list'])
        ;

        $collection = $this->service->all();

        self::assertInstanceOf(GroupCollection::class, $collection);
        self::assertCount(0, $collection);
    }

    public function testAllPassesNoOrderWhenOnlyDirectionProvided(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups', ['order' => null])
            ->willReturn([])
        ;

        $this->service->all(direction: SortOrder::Ascending);
    }

    // -------------------------------------------------------------------------
    // getReceivers()
    // -------------------------------------------------------------------------

    public function testGetReceiversPassesDefaultParameters(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', [
                'page' => 0,
                'pagesize' => 50,
                'type' => null,
                'detail' => null,
                'email_list' => null,
                'id_list' => null,
                'order_by' => null,
            ])
            ->willReturn([])
        ;

        $result = $this->service->getReceivers(self::GROUP_ID);

        self::assertInstanceOf(ReceiverCollection::class, $result);
        self::assertCount(0, $result);
    }

    public function testGetReceiversPassesTypeFilter(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', self::callback(static function (array $params): bool {
                return $params['type'] === 'active';
            }))
            ->willReturn([])
        ;

        $this->service->getReceivers(self::GROUP_ID, type: ReceiverType::Active);
    }

    public function testGetReceiversBuildsEmailListAsCommaSeparatedString(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', self::callback(static function (array $params): bool {
                return $params['email_list'] === 'a@example.com,b@example.com';
            }))
            ->willReturn([])
        ;

        $this->service->getReceivers(self::GROUP_ID, emailList: ['a@example.com', 'b@example.com']);
    }

    public function testGetReceiversBuildsIdListAsCommaSeparatedString(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', self::callback(static function (array $params): bool {
                return $params['id_list'] === '1,2,3';
            }))
            ->willReturn([])
        ;

        $this->service->getReceivers(self::GROUP_ID, idList: ['1', '2', '3']);
    }

    public function testGetReceiversPassesOrderByWithDirection(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', self::callback(static function (array $params): bool {
                return $params['order_by'] === 'email ASC';
            }))
            ->willReturn([])
        ;

        $this->service->getReceivers(
            self::GROUP_ID,
            orderBy: 'email',
            orderDirection: SortOrder::Ascending
        );
    }

    public function testGetReceiversPassesOrderByWithoutDirectionTrimsTrailingSpace(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', self::callback(static function (array $params): bool {
                return $params['order_by'] === 'email';
            }))
            ->willReturn([])
        ;

        $this->service->getReceivers(self::GROUP_ID, orderBy: 'email');
    }

    public function testGetReceiversPassesDetailDepth(): void {
        $this->requestor
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'groups/'.self::GROUP_ID.'/receivers', self::callback(static function (array $params): bool {
                return $params['detail'] === 3; // events (1) + orders (2)
            }))
            ->willReturn([])
        ;

        $this->service->getReceivers(self::GROUP_ID, detail: 3);
    }

    public function testGetReceiversReturnsEmptyCollectionWhenResponseIsNotAList(): void {
        $this->requestor
            ->method('request')
            ->willReturn(['id' => 1, 'email' => 'not-a-list@example.com'])
        ;

        $result = $this->service->getReceivers(self::GROUP_ID);

        self::assertInstanceOf(ReceiverCollection::class, $result);
        self::assertCount(0, $result);
    }

    public function testGetReceiversMapsResponseToReceiverCollection(): void {
        $this->requestor
            ->method('request')
            ->willReturn([
                ['id' => 1, 'email' => 'alice@example.com'],
                ['id' => 2, 'email' => 'bob@example.com'],
            ])
        ;

        $result = $this->service->getReceivers(self::GROUP_ID);

        self::assertCount(2, $result);
        $receivers = $result->toArray();
        self::assertSame('alice@example.com', $receivers[0]->email);
        self::assertSame('bob@example.com', $receivers[1]->email);
    }
}
