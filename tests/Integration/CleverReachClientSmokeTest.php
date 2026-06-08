<?php

declare(strict_types=1);

namespace CleverReach\Tests\Integration;

use CleverReach\SDK\CleverReachClient;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CleverReachClient::class)]
final class CleverReachClientSmokeTest extends TestCase
{
    public function testClientWorksWithConcretePsrImplementations(): void {
        $httpClient = new MockHttpClient();
        $psr17Factory = new Psr17Factory();

        $httpClient->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'));

        $client = new CleverReachClient(
            apiToken: 'token',
            httpClient: $httpClient,
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory
        );

        $result = $client->request(
            'POST',
            'groups/42/receivers',
            [],
            ['email' => 'dev@example.com']
        );

        $request = $httpClient->getLastRequest();

        self::assertSame(['ok' => true], $result);
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://rest.cleverreach.com/v3/groups/42/receivers', (string) $request->getUri());
        self::assertSame('Bearer token', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('{"email":"dev@example.com"}', (string) $request->getBody());
    }
}
