<?php

declare(strict_types=1);

namespace CleverReach\Tests\Service;

use CleverReach\SDK\CleverReachClient;
use CleverReach\SDK\Http\ApiRequestorInterface;
use CleverReach\SDK\Http\ScalarApiRequestorInterface;
use CleverReach\SDK\Model\AbandonedCartAutomationRequest;
use CleverReach\SDK\Model\AbandonedCartRequest;
use CleverReach\SDK\Model\AbandonedCartTriggerRequest;
use CleverReach\SDK\Model\AttributeRequest;
use CleverReach\SDK\Model\BlacklistRequest;
use CleverReach\SDK\Model\DynamicContentRequest;
use CleverReach\SDK\Model\FormSendRequest;
use CleverReach\SDK\Model\FormTemplateRequest;
use CleverReach\SDK\Model\GroupRequest;
use CleverReach\SDK\Model\ReceiverRequest;
use CleverReach\SDK\Model\SegmentRequest;
use CleverReach\SDK\Model\SyncSettingsRequest;
use CleverReach\SDK\Model\WebhookEventRequest;
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
            ->willReturn($response);

        $service = new $serviceClass($requestor);
        $service->{$serviceMethod}(...$arguments);

        $this->addToAssertionCount(1);
    }

    /** @return iterable<string, array<int, mixed>> */
    public static function endpointProvider(): iterable {
        yield 'attributes all' => [AttributesService::class, 'all', [], 'GET', 'attributes', [], null, []];
        yield 'attributes create' => [AttributesService::class, 'create', [new AttributeRequest('shop')], 'POST', 'attributes', [], ['name' => 'shop', 'type' => 'text', 'description' => ''], []];
        yield 'attributes update' => [AttributesService::class, 'update', [7, new AttributeRequest('shop')], 'PUT', 'attributes/7', [], ['name' => 'shop', 'type' => 'text', 'description' => ''], []];
        yield 'segments all' => [SegmentsService::class, 'all', [21], 'GET', 'groups/21/filters', [], null, []];
        yield 'segments create' => [SegmentsService::class, 'create', [21, new SegmentRequest('VIP', [])], 'POST', 'groups/21/filters', [], ['name' => 'VIP', 'description' => '', 'rules' => []], []];
        yield 'segments update' => [SegmentsService::class, 'update', [21, 7, new SegmentRequest('VIP', [])], 'PUT', 'groups/21/filters/7', [], ['name' => 'VIP', 'description' => '', 'rules' => []], []];
        yield 'segments delete' => [SegmentsService::class, 'delete', [21, 7], 'DELETE', 'groups/21/filters/7', [], null, []];
        yield 'dynamic content all' => [DynamicContentService::class, 'all', [], 'GET', 'mycontent', [], null, []];
        yield 'dynamic content create' => [DynamicContentService::class, 'create', [new DynamicContentRequest('Products', 'https://example.test/products', 'secret')], 'POST', 'mycontent', [], ['name' => 'Products', 'url' => 'https://example.test/products', 'password' => 'secret', 'cors' => true, 'icon' => '', 'type' => 'product'], []];
        yield 'dynamic content update' => [DynamicContentService::class, 'update', [7, new DynamicContentRequest('Products', 'https://example.test/products', 'secret')], 'PUT', 'mycontent/7', [], ['name' => 'Products', 'url' => 'https://example.test/products', 'password' => 'secret', 'cors' => true, 'icon' => '', 'type' => 'product'], []];
        yield 'dynamic content delete' => [DynamicContentService::class, 'delete', [7], 'DELETE', 'mycontent/7', [], null, []];
        yield 'forms get' => [FormsService::class, 'get', [9], 'GET', 'forms/9', [], null, []];
        yield 'forms by group' => [FormsService::class, 'byGroup', [21], 'GET', 'groups/21/forms', [], null, []];
        yield 'forms create from template' => [FormsService::class, 'createFromTemplate', [21, new FormTemplateRequest('doi', 'DOI')], 'POST', 'forms/21/createfromtemplate/doi', [], ['name' => 'DOI'], []];
        yield 'forms send' => [FormsService::class, 'send', [9, 'doi', new FormSendRequest('a@example.com')], 'POST', 'forms/9/send/doi', [], ['email' => 'a@example.com', 'doidata' => []], []];
        yield 'automation create abandoned cart' => [AutomationService::class, 'createAbandonedCart', [new AbandonedCartAutomationRequest('Cart', 'store', 'JTLShop')], 'POST', 'automation/createfromtemplate/abandonedcart', [], ['name' => 'Cart', 'storeid' => 'store', 'integration' => 'JTLShop', 'settings' => []], []];
        yield 'webhook register' => [WebhookEventsService::class, 'register', [new WebhookEventRequest('https://example.test/hook', 'automation', '21', 'token')], 'POST', 'https://rest.cleverreach.com/hooks/eventhook', [], ['url' => 'https://example.test/hook', 'event' => 'automation', 'condition' => '21', 'verify' => 'token'], []];
        yield 'webhook delete' => [WebhookEventsService::class, 'delete', ['21', 'automation'], 'DELETE', 'https://rest.cleverreach.com/hooks/eventhook/automation', ['condition' => '21'], null, []];
        yield 'stats group' => [StatsService::class, 'group', [21], 'GET', 'groups/21/stats', [], null, []];
        yield 'clients payment plan' => [ClientsService::class, 'paymentPlan', [7], 'GET', 'clients/7/plan', [], null, []];
        yield 'clients users' => [ClientsService::class, 'users', [7], 'GET', 'clients/7/users', [], null, []];
        yield 'debug validate' => [DebugService::class, 'validate', [], 'GET', 'debug/validate', [], null, []];
        yield 'debug whoami' => [DebugService::class, 'whoami', [], 'GET', 'debug/whoami', [], null, []];
        yield 'sync settings update' => [SyncSettingsService::class, 'update', [new SyncSettingsRequest(['interval' => 'immediate'])], 'POST', 'oauth/settings', [], ['interval' => 'immediate'], []];
        yield 'reports get' => [ReportsService::class, 'get', [9], 'GET', 'reports/9', [], null, []];
        yield 'groups create' => [GroupsService::class, 'create', [new GroupRequest('Newsletter')], 'POST', 'groups', [], ['name' => 'Newsletter', 'backup' => false, 'locked' => false, 'receiver_info' => ''], ['id' => 1, 'name' => 'Newsletter']];
        yield 'receivers upsert plus' => [ReceiversService::class, 'upsertPlus', [21, [new ReceiverRequest('a@example.com')]], 'POST', 'groups/21/receivers/upsertplus', [], [['email' => 'a@example.com']], []];
        yield 'receivers delete' => [ReceiversService::class, 'delete', ['a@example.com'], 'DELETE', 'receivers/a@example.com', [], null, []];
        yield 'receivers delete batch' => [ReceiversService::class, 'deleteBatch', [['a@example.com']], 'POST', 'receivers/delete', [], ['receivers' => ['a@example.com']], []];
        yield 'receivers blacklist' => [ReceiversService::class, 'blacklist', [new BlacklistRequest('a@example.com', 21)], 'POST', 'blacklist', [], ['email' => 'a@example.com', 'group_id' => 21], []];
        yield 'receivers whitelist' => [ReceiversService::class, 'whitelist', ['a@example.com'], 'DELETE', 'blacklist/a@example.com', [], null, []];
        yield 'receivers validate blacklist' => [ReceiversService::class, 'validateBlacklist', [21, ['a@example.com']], 'POST', 'blacklist/validate', [], ['emails' => ['a@example.com'], 'group_id' => 21, 'invert' => true], []];
        yield 'receivers filter by email' => [ReceiversService::class, 'filterByEmail', [21, 'a@example.com'], 'POST', 'receivers/filter', [], ['groups' => [21], 'rules' => [['field' => 'email', 'logic' => 'eq', 'condition' => 'a@example.com']]], []];
        yield 'receivers get from group' => [ReceiversService::class, 'getFromGroup', [21, 7], 'GET', 'groups/21/receivers/7', [], null, ['id' => 7, 'email' => 'a@example.com']];
    }

    public function testScalarEndpointMethods(): void {
        $requestor = $this->createMock(ScalarApiRequestorInterface::class);
        $requestor->expects(self::exactly(4))
            ->method('requestScalar')
            ->willReturnCallback(static function (string $method, string $uri): mixed {
                return match ($uri) {
                    'tags/count' => '17',
                    'debug/ping' => 'pong',
                    'clients/7/activereceivercount' => 23,
                    'trigger/abandonedcart' => 0,
                    default => null,
                };
            });

        self::assertSame(17, (new StatsService($requestor))->countByTag('Subscriber', 21));
        self::assertSame('pong', (new DebugService($requestor))->ping());
        self::assertSame(23, (new ClientsService($requestor))->activeReceiversCount(7));
        $cart = new AbandonedCartRequest('EUR', 'store', 1.0, 0.0, [], 'https://example.test/cart');
        self::assertSame(0, (new AutomationService($requestor))->triggerAbandonedCart(
            new AbandonedCartTriggerRequest($cart, '21', 'a@example.com')
        ));
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
