<?php

declare(strict_types=1);

namespace CleverReach\SDK;

use CleverReach\SDK\Auth\TokenProviderInterface;
use CleverReach\SDK\Exception\AuthenticationException;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\MissingDependencyException;
use CleverReach\SDK\Http\ApiRequestor;
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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Entry point for the CleverReach PHP SDK.
 *
 * Instantiate this class with your API token and use the typed service methods
 * for a clean, IDE-friendly experience, or use `request()` directly for any endpoint
 * not yet covered by a service.
 *
 * @example
 * ```php
 * // Basic usage with a static API Token
 * $client = new CleverReachClient('YOUR_API_TOKEN');
 *
 * // Advanced usage with OAuth 2.0 flow
 * $oauth = new CleverReach\SDK\Auth\OAuthHelper('CLIENT_ID', 'CLIENT_SECRET', 'https://your-domain.com/callback');
 * $client = new CleverReachClient();
 * $client->setTokenProvider($oauth);
 *
 * // Typed service API (recommended)
 * $groups = $client->groups()->all();
 * $receiver = $client->receivers()->get('jane@example.com');
 *
 * // Raw access for any endpoint
 * $stats = $client->request('GET', "groups/{$id}/stats");
 * ```
 *
 * @throws AuthenticationException    When the API token is invalid or missing
 * @throws MissingDependencyException When no PSR-18 HTTP client is found
 */
final class CleverReachClient
{
    private readonly ApiRequestor $requestor;
    private ?AttributesService $attributesService = null;
    private ?AutomationService $automationService = null;
    private ?ClientsService $clientsService = null;
    private ?DebugService $debugService = null;
    private ?DynamicContentService $dynamicContentService = null;
    private ?FormsService $formsService = null;
    private ?GroupsService $groupsService = null;
    private ?ReceiversService $receiversService = null;
    private ?ReportsService $reportsService = null;
    private ?SegmentsService $segmentsService = null;
    private ?StatsService $statsService = null;
    private ?SyncSettingsService $syncSettingsService = null;
    private ?WebhookEventsService $webhookEventsService = null;

    public function __construct(
        string $apiToken = '',
        string $baseUri = 'https://rest.cleverreach.com/v3/',
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->requestor = new ApiRequestor(
            $apiToken,
            $baseUri,
            $httpClient,
            $requestFactory,
            $streamFactory
        );
    }

    /**
     * Sets a custom TokenProvider (e.g., an OAuthHelper instance) which will
     * be responsible for injecting a valid Bearer token into outgoing requests.
     */
    public function setTokenProvider(TokenProviderInterface $tokenProvider): void {
        $this->requestor->setTokenProvider($tokenProvider);
    }

    /**
     * Sends a raw request to any CleverReach API endpoint.
     *
     * Useful for endpoints not yet covered by a dedicated service method.
     *
     * @param array<string, null|scalar> $query URL query parameters (null values are ignored)
     * @param null|array<string, mixed>|list<array<string, mixed>>  $json  Request body, JSON-encoded automatically
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     *
     * @throws AuthenticationException On 401 responses
     * @throws CleverReachException    On any other API or network error
     */
    public function request(string $method, string $endpoint, array $query = [], ?array $json = null): array {
        return $this->requestor->request($method, $endpoint, $query, $json);
    }

    /**
     * Sends a raw request to an endpoint returning a scalar JSON value.
     *
     * @param array<string, null|scalar> $query URL query parameters (null values are ignored)
     * @param null|array<string, mixed>|list<array<string, mixed>>  $json  Request body, JSON-encoded automatically
     */
    public function requestScalar(string $method, string $endpoint, array $query = [], ?array $json = null): mixed {
        return $this->requestor->requestScalar($method, $endpoint, $query, $json);
    }

    public function attributes(): AttributesService {
        return $this->attributesService ??= new AttributesService($this->requestor);
    }

    public function automation(): AutomationService {
        return $this->automationService ??= new AutomationService($this->requestor);
    }

    public function clients(): ClientsService {
        return $this->clientsService ??= new ClientsService($this->requestor);
    }

    public function debug(): DebugService {
        return $this->debugService ??= new DebugService($this->requestor);
    }

    public function dynamicContent(): DynamicContentService {
        return $this->dynamicContentService ??= new DynamicContentService($this->requestor);
    }

    public function forms(): FormsService {
        return $this->formsService ??= new FormsService($this->requestor);
    }

    public function groups(): GroupsService {
        return $this->groupsService ??= new GroupsService($this->requestor);
    }

    public function receivers(): ReceiversService {
        return $this->receiversService ??= new ReceiversService($this->requestor);
    }

    public function reports(): ReportsService {
        return $this->reportsService ??= new ReportsService($this->requestor);
    }

    public function segments(): SegmentsService {
        return $this->segmentsService ??= new SegmentsService($this->requestor);
    }

    public function stats(): StatsService {
        return $this->statsService ??= new StatsService($this->requestor);
    }

    public function syncSettings(): SyncSettingsService {
        return $this->syncSettingsService ??= new SyncSettingsService($this->requestor);
    }

    public function webhookEvents(): WebhookEventsService {
        return $this->webhookEventsService ??= new WebhookEventsService($this->requestor);
    }
}
