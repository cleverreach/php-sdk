<?php

declare(strict_types=1);

namespace CleverReach\Tests;

use CleverReach\SDK\CleverReachClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
#[CoversClass(CleverReachClient::class)]
final class CleverReachClientTest extends TestCase
{
    public function testRequestAcceptsSeparateQueryAndJsonPayload(): void {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $jsonStream = $this->createMock(StreamInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $responseBody = $this->createMock(StreamInterface::class);

        $requestFactory
            ->expects(self::once())
            ->method('createRequest')
            ->with('POST', 'https://rest.cleverreach.com/v3/groups/42/receivers')
            ->willReturn($request)
        ;

        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->with($jsonStream)->willReturnSelf();

        $streamFactory
            ->expects(self::once())
            ->method('createStream')
            ->with('{"email":"dev@example.com"}')
            ->willReturn($jsonStream)
        ;

        $httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response)
        ;

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('__toString')->willReturn('{"ok":true}');

        $client = new CleverReachClient(
            apiToken: 'token',
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory
        );

        $result = $client->request('POST', 'groups/42/receivers', [], ['email' => 'dev@example.com']);

        self::assertSame(['ok' => true], $result);
    }
}
