<?php

declare(strict_types=1);

namespace CleverReach\Tests\Service;

use CleverReach\SDK\CleverReachClient;
use CleverReach\SDK\Http\ApiRequestorInterface;
use CleverReach\SDK\Http\ScalarApiRequestorInterface;
use CleverReach\SDK\Service\AttributesService;
use CleverReach\SDK\Service\AutomationService;
use CleverReach\SDK\Service\ClientsService;
use CleverReach\SDK\Service\DebugService;
use CleverReach\SDK\Service\DynamicContentService;
use CleverReach\SDK\Service\FormsService;
use CleverReach\SDK\Service\GroupsService;
use CleverReach\SDK\Service\ReceiversService;
use CleverReach\SDK\Service\ReportsService;
use CleverReach\SDK\Service\SegmentsService;
use CleverReach\SDK\Service\StatsService;
use CleverReach\SDK\Service\SyncSettingsService;
use CleverReach\SDK\Service\WebhookEventsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ExpandedServicesTest extends TestCase
{
    /**
     * @param class-string $serviceClass
     * @param array<int, mixed> $arguments
     * @param array<string, mixed> $query
     * @param array<string, mixed>|list<array<string, mixed>>|null $json
     * @param array<string, mixed>|list<array<string, mixed>> $response
     */
    #[DataProvider('endpointProvider')]
    public function testExpandedServiceEndpoints(
        string $serviceClass,
        string $serviceMethod,
        array $arguments,
        string $httpMethod,
        string $uri,
        array $query,
        ?array $json,
        array $response
    ): void {
        $requestor = $this->createMock(ApiRequestorInterface::class);
        $expectedArguments = [$httpMethod, $uri];
        if ($query !== [] || $json !== null) {
            $expectedArguments[] = $query;
        }
        if ($json !== null) {
            $expectedArguments[] = $json;
        }

        $requestor->expects(self::once())
            ->method('request')
            ->with(...$expectedArguments)
            ->willReturn($response)
        ;

        $service = new $serviceClass($requestor);
        $service->{$serviceMethod}(...$arguments);

        $this->addToAssertionCount(1);
    }

    /** @return iterable<string, array<int, mixed>> */
    public static function endpointProvider(): iterable {
        yield 'attributes all' => [AttributesService::class, 'all', [], 'GET', 'attributes.json', [], null, []];
        yield 'attributes create' => [AttributesService::class, 'create', [['name' => 'shop', 'type' => 'text']], 'POST', 'attributes.json', [], ['name' => 'shop', 'type' => 'text'], []];
        yield 'attributes update' => [AttributesService::class, 'update', [7, ['name' => 'shop']], 'PUT', 'attributes.json/7', [], ['name' => 'shop'], []];
        yield 'segments all' => [SegmentsService::class, 'all', [21], 'GET', 'groups.json/21/filters', [], null, []];
        yield 'segments create' => [SegmentsService::class, 'create', [21, ['name' => 'VIP']], 'POST', 'groups.json/21/filters', [], ['name' => 'VIP'], []];
        yield 'segments update' => [SegmentsService::class, 'update', [21, 7, ['name' => 'VIP']], 'PUT', 'groups.json/21/filters/7', [], ['name' => 'VIP'], []];
        yield 'segments delete' => [SegmentsService::class, 'delete', [21, 7], 'DELETE', 'groups.json/21/filters/7', [], null, []];
        yield 'dynamic content all' => [DynamicContentService::class, 'all', [], 'GET', 'mycontent.json', [], null, []];
        yield 'dynamic content create' => [DynamicContentService::class, 'create', [['name' => 'Products']], 'POST', 'mycontent.json', [], ['name' => 'Products'], []];
        yield 'dynamic content update' => [DynamicContentService::class, 'update', [7, ['name' => 'Products']], 'PUT', 'mycontent.json/7', [], ['name' => 'Products'], []];
        yield 'dynamic content delete' => [DynamicContentService::class, 'delete', [7], 'DELETE', 'mycontent.json/7', [], null, []];
        yield 'forms get' => [FormsService::class, 'get', [9], 'GET', 'forms.json/9', [], null, []];
        yield 'forms by group' => [FormsService::class, 'byGroup', [21], 'GET', 'groups.json/21/forms', [], null, []];
        yield 'forms create from template' => [FormsService::class, 'createFromTemplate', [21, 'doi', ['name' => 'DOI']], 'POST', 'forms.json/21/createfromtemplate/doi', [], ['name' => 'DOI'], []];
        yield 'forms send' => [FormsService::class, 'send', [9, 'doi', ['email' => 'a@example.com', 'doidata' => []]], 'POST', 'forms.json/9/send/doi', [], ['email' => 'a@example.com', 'doidata' => []], []];
        yield 'automation create abandoned cart' => [AutomationService::class, 'createAbandonedCart', [['name' => 'Cart']], 'POST', 'automation/createfromtemplate/abandonedcart.json', [], ['name' => 'Cart'], []];
        yield 'webhook register' => [WebhookEventsService::class, 'register', [['event' => 'automation', 'condition' => '21', 'url' => 'https://example.test/hook', 'verify' => 'token']], 'POST', 'hooks/eventhook', [], ['event' => 'automation', 'condition' => '21', 'url' => 'https://example.test/hook', 'verify' => 'token'], []];
        yield 'webhook delete' => [WebhookEventsService::class, 'delete', ['21', 'automation'], 'DELETE', 'hooks/eventhook/automation', ['condition' => '21'], null, []];
        yield 'stats group' => [StatsService::class, 'group', [21], 'GET', 'groups.json/21/stats', [], null, []];
        yield 'clients payment plan' => [ClientsService::class, 'paymentPlan', [7], 'GET', 'clients.json/7/plan', [], null, []];
        yield 'clients users' => [ClientsService::class, 'users', [7], 'GET', 'clients.json/7/users', [], null, []];
        yield 'debug validate' => [DebugService::class, 'validate', [], 'GET', 'debug/validate.json', [], null, []];
        yield 'debug whoami' => [DebugService::class, 'whoami', [], 'GET', 'debug/whoami.json', [], null, []];
        yield 'sync settings update' => [SyncSettingsService::class, 'update', [['interval' => 'immediate']], 'POST', 'oauth/settings.json', [], ['interval' => 'immediate'], []];
        yield 'reports get' => [ReportsService::class, 'get', [9], 'GET', 'reports.json/9', [], null, []];
        yield 'groups create' => [GroupsService::class, 'create', [['name' => 'Newsletter']], 'POST', 'groups.json', [], ['name' => 'Newsletter'], ['id' => 1, 'name' => 'Newsletter']];
        yield 'receivers upsert plus' => [ReceiversService::class, 'upsertPlus', [21, [['email' => 'a@example.com']]], 'POST', 'groups.json/21/receivers/upsertplus', [], [['email' => 'a@example.com']], []];
        yield 'receivers delete' => [ReceiversService::class, 'delete', ['a@example.com'], 'DELETE', 'receivers.json/a@example.com', [], null, []];
        yield 'receivers delete batch' => [ReceiversService::class, 'deleteBatch', [['a@example.com']], 'POST', 'receivers/delete.json', [], ['receivers' => ['a@example.com']], []];
        yield 'receivers blacklist' => [ReceiversService::class, 'blacklist', [['email' => 'a@example.com']], 'POST', 'blacklist.json/', [], ['email' => 'a@example.com'], []];
        yield 'receivers whitelist' => [ReceiversService::class, 'whitelist', ['a@example.com'], 'DELETE', 'blacklist.json/a@example.com', [], null, []];
        yield 'receivers validate blacklist' => [ReceiversService::class, 'validateBlacklist', [21, ['a@example.com']], 'POST', 'blacklist/validate.json', [], ['emails' => ['a@example.com'], 'group_id' => 21, 'invert' => true], []];
        yield 'receivers filter by email' => [ReceiversService::class, 'filterByEmail', [21, 'a@example.com'], 'POST', 'receivers/filter.json', [], ['groups' => [21], 'rules' => [['field' => 'email', 'logic' => 'eq', 'condition' => 'a@example.com']]], []];
        yield 'receivers get from group' => [ReceiversService::class, 'getFromGroup', [21, 7], 'GET', 'groups.json/21/receivers/7', [], null, ['id' => 7, 'email' => 'a@example.com']];
    }

    public function testScalarEndpointMethods(): void {
        $requestor = $this->createMock(ScalarApiRequestorInterface::class);
        $requestor->expects(self::exactly(4))
            ->method('requestScalar')
            ->willReturnCallback(static function (string $method, string $uri): mixed {
                return match ($uri) {
                    'tags/count.json' => '17',
                    'debug/ping.json' => 'pong',
                    'clients.json/7/receivercount' => '23',
                    'trigger/abandonedcart.json' => 0,
                    default => null,
                };
            })
        ;

        self::assertSame(17, (new StatsService($requestor))->countByTag('Subscriber', 21));
        self::assertSame('pong', (new DebugService($requestor))->ping());
        self::assertSame('23', (new ClientsService($requestor))->activeReceiversCount(7));
        (new AutomationService($requestor))->triggerAbandonedCart(['poolid' => 'a@example.com']);
    }

    public function testLegacyRequestorDoesNotNeedScalarMethod(): void {
        $requestor = new class implements ApiRequestorInterface {
            public function request(
                string $method,
                string $uri,
                array $query = [],
                ?array $json = null
            ): array {
                return [];
            }
        };

        self::assertSame(0, iterator_count((new GroupsService($requestor))->all()));
    }

    public function testClientProvidesExpandedServices(): void {
        $client = new CleverReachClient(
            'token',
            'https://rest.cleverreach.com/v3/',
            $this->createMock(ClientInterface::class),
            $this->createMock(RequestFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );

        $services = [
            'attributes' => AttributesService::class,
            'automation' => AutomationService::class,
            'clients' => ClientsService::class,
            'debug' => DebugService::class,
            'dynamicContent' => DynamicContentService::class,
            'forms' => FormsService::class,
            'reports' => ReportsService::class,
            'segments' => SegmentsService::class,
            'stats' => StatsService::class,
            'syncSettings' => SyncSettingsService::class,
            'webhookEvents' => WebhookEventsService::class,
        ];

        foreach ($services as $method => $class) {
            self::assertInstanceOf($class, $client->{$method}());
            self::assertSame($client->{$method}(), $client->{$method}());
        }
    }
}
