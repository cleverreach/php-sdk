<?php

declare(strict_types=1);

namespace CleverReach\Tests\Http;

use CleverReach\SDK\Auth\TokenProviderInterface;
use CleverReach\SDK\Exception\AuthenticationException;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\RateLimitExceededException;
use CleverReach\SDK\Exception\ResourceNotFoundException;
use CleverReach\SDK\Exception\ValidationException;
use CleverReach\SDK\Http\ApiRequestor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
#[CoversClass(ApiRequestor::class)]
final class ApiRequestorTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;
    private MockObject&RequestFactoryInterface $requestFactory;
    private MockObject&StreamFactoryInterface $streamFactory;
    private MockObject&RequestInterface $request;
    private MockObject&ResponseInterface $response;
    private MockObject&StreamInterface $responseBody;

    protected function setUp(): void {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseBody = $this->createMock(StreamInterface::class);
    }

    public function testRequestBuildsQueryAndDecodesResponse(): void {
        $this->requestFactory
            ->expects(self::once())
            ->method('createRequest')
            ->with('GET', 'https://rest.cleverreach.com/v3/groups/123/receivers?pagesize=50&page=0')
            ->willReturn($this->request)
        ;

        $this->request->method('withHeader')->willReturnSelf();

        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->request)
            ->willReturn($this->response)
        ;

        $this->response->method('getStatusCode')->willReturn(200);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"result":"ok"}');

        // 'type' => null is filtered out; pagesize and page are kept
        $result = $this->makeRequestor()->request('GET', 'groups/123/receivers', ['pagesize' => 50, 'page' => 0, 'type' => null]);

        self::assertSame(['result' => 'ok'], $result);
    }

    public function testRequestSendsJsonPayloadWhenProvided(): void {
        $jsonStream = $this->createMock(StreamInterface::class);
        $headers = [];

        $this->requestFactory
            ->expects(self::once())
            ->method('createRequest')
            ->with('POST', 'https://rest.cleverreach.com/v3/groups/123/receivers')
            ->willReturn($this->request)
        ;

        $this->request
            ->method('withHeader')
            ->willReturnCallback(function (string $name, string $value) use (&$headers): RequestInterface {
                $headers[$name] = $value;

                return $this->request;
            })
        ;

        $this->request
            ->expects(self::once())
            ->method('withBody')
            ->with($jsonStream)
            ->willReturnSelf()
        ;

        $this->streamFactory
            ->expects(self::once())
            ->method('createStream')
            ->with('{"email":"dev@example.com"}')
            ->willReturn($jsonStream)
        ;

        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->request)
            ->willReturn($this->response)
        ;

        $this->response->method('getStatusCode')->willReturn(200);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"id":123}');

        $result = $this->makeRequestor()->request('POST', 'groups/123/receivers', [], ['email' => 'dev@example.com']);

        self::assertSame(['id' => 123], $result);
        self::assertSame('application/json', $headers['Content-Type'] ?? null);
    }

    public function testRequestThrowsAuthenticationExceptionOn401(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(401);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"error":"Invalid token"}');

        try {
            $this->makeRequestor()->request('GET', 'groups');
            self::fail('Expected AuthenticationException was not thrown.');
        } catch (AuthenticationException $exception) {
            self::assertSame('Invalid token', $exception->getMessage());
            self::assertSame(401, $exception->statusCode());
            self::assertSame('{"error":"Invalid token"}', $exception->responseBody());
        }
    }

    public function testRequestThrowsValidationExceptionOn400(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(400);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"error":"Invalid email address"}');

        try {
            $this->makeRequestor()->request('POST', 'groups/123/receivers');
            self::fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            self::assertSame('Invalid email address', $exception->getMessage());
            self::assertSame(400, $exception->statusCode());
        }
    }

    public function testRequestThrowsResourceNotFoundExceptionOn404(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(404);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"error":"Group not found"}');

        try {
            $this->makeRequestor()->request('GET', 'groups/9999');
            self::fail('Expected ResourceNotFoundException was not thrown.');
        } catch (ResourceNotFoundException $exception) {
            self::assertSame('Group not found', $exception->getMessage());
            self::assertSame(404, $exception->statusCode());
        }
    }

    public function testRequestThrowsRateLimitExceededExceptionOn429(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(429);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"error":"Too many requests"}');

        try {
            $this->makeRequestor()->request('GET', 'groups');
            self::fail('Expected RateLimitExceededException was not thrown.');
        } catch (RateLimitExceededException $exception) {
            self::assertSame('Too many requests', $exception->getMessage());
            self::assertSame(429, $exception->statusCode());
        }
    }

    public function testRequestWrapsHttpClientExceptions(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();

        $networkException = new class('network down') extends \RuntimeException implements ClientExceptionInterface {};

        $this->httpClient
            ->method('sendRequest')
            ->willThrowException($networkException)
        ;

        try {
            $this->makeRequestor()->request('GET', 'groups');
            self::fail('Expected CleverReachException was not thrown.');
        } catch (CleverReachException $exception) {
            self::assertSame('HTTP communication with CleverReach failed.', $exception->getMessage());
            self::assertSame($networkException, $exception->getPrevious());
        }
    }

    public function testRequestDecodesResponseWhenApiTokenIsEmpty(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->request->method('withBody')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(200);
        $this->response->method('getBody')->willReturn($this->responseBody);

        // Return null/empty
        $this->responseBody->method('__toString')->willReturn('{"ok": true}');

        $requestor = new ApiRequestor('', 'https://rest.cleverreach.com/v3/', $this->httpClient, $this->requestFactory, $this->streamFactory);

        $result = $requestor->request('GET', 'groups', [], ['some' => 'json']);
        self::assertSame(['ok' => true], $result);
    }

    public function testRequestEncodesJsonException(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();

        // This will simulate json_encode failing due to INF float or recursive deps
        $recursive = [];
        $recursive['a'] = &$recursive;

        $requestor = new ApiRequestor('token', 'https://rest.cleverreach.com/v3/', $this->httpClient, $this->requestFactory, $this->streamFactory);

        $this->expectException(CleverReachException::class);
        $this->expectExceptionMessage('Failed to encode CleverReach API request JSON.');

        $requestor->request('POST', 'groups', [], $recursive);
    }

    public function testRequestRetriesExactlyOnceOn401WithTokenProvider(): void {
        $provider = $this->createMock(TokenProviderInterface::class);
        $provider->expects(self::exactly(2))
            ->method('getAccessToken')
            ->willReturnOnConsecutiveCalls('token_1', 'token_2')
        ;

        $requestor = $this->makeRequestor();
        $requestor->setTokenProvider($provider);

        $this->requestFactory->method('createRequest')->willReturn($this->request);

        $this->request->expects(self::any())
            ->method('withHeader')
            ->willReturnCallback(function (string $name, string $value) {
                return $this->request;
            })
        ;

        $this->httpClient->expects(self::exactly(2))
            ->method('sendRequest')
            ->willReturn($this->response)
        ;

        $this->response->expects(self::exactly(2))
            ->method('getStatusCode')
            ->willReturnOnConsecutiveCalls(401, 200)
        ;

        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"result":"success"}');

        $result = $requestor->request('GET', 'groups');
        self::assertSame(['result' => 'success'], $result);
    }

    public function testRequestThrowsOnSecond401WithTokenProvider(): void {
        $provider = $this->createMock(TokenProviderInterface::class);
        $provider->expects(self::exactly(2))
            ->method('getAccessToken')
            ->willReturnOnConsecutiveCalls('token_1', 'token_2')
        ;

        $requestor = $this->makeRequestor();
        $requestor->setTokenProvider($provider);

        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();

        $this->httpClient->expects(self::exactly(2))
            ->method('sendRequest')
            ->willReturn($this->response)
        ;

        $this->response->expects(self::exactly(2))
            ->method('getStatusCode')
            ->willReturnOnConsecutiveCalls(401, 401)
        ;

        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"error":"Still unauthorized"}');

        try {
            $requestor->request('GET', 'groups');
            self::fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame('Still unauthorized', $e->getMessage());
            self::assertSame(401, $e->statusCode());
        }
    }

    public function testRequestReturnsEmptyArrayForEmptyResponseBody(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(204);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('');

        $result = $this->makeRequestor()->request('GET', 'groups');

        self::assertSame([], $result);
    }

    public function testRequestThrowsExceptionForNonArrayJsonResponse(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(200);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('1');

        try {
            $this->makeRequestor()->request('GET', 'groups');
            self::fail('Expected CleverReachException was not thrown.');
        } catch (CleverReachException $exception) {
            self::assertSame('Unexpected response format from CleverReach API.', $exception->getMessage());
        }
    }

    public function testRequestThrowsStatusFallbackMessageWhenErrorBodyHasNoKnownMessageFields(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(500);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"details":"backend unavailable"}');

        try {
            $this->makeRequestor()->request('GET', 'groups');
            self::fail('Expected CleverReachException was not thrown.');
        } catch (CleverReachException $exception) {
            self::assertSame('CleverReach API request failed with status 500.', $exception->getMessage());
            self::assertSame(500, $exception->statusCode());
        }
    }

    public function testRequestWrapsJsonDecodeFailuresInCleverReachException(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->httpClient->method('sendRequest')->willReturn($this->response);

        $this->response->method('getStatusCode')->willReturn(200);
        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->responseBody->method('__toString')->willReturn('{"broken":');

        try {
            $this->makeRequestor()->request('GET', 'groups');
            self::fail('Expected CleverReachException was not thrown.');
        } catch (CleverReachException $exception) {
            self::assertSame('Failed to decode CleverReach API response JSON.', $exception->getMessage());
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        }
    }

    public function testBuildErrorMessageReturnsFallbackWhenStatusCodeIsNullAndBodyHasNoMessage(): void {
        $requestor = $this->makeRequestor();
        $method = new \ReflectionMethod($requestor, 'buildErrorMessage');

        $message = $method->invoke($requestor, null, null, 'fallback message');

        self::assertSame('fallback message', $message);
    }

    private function makeRequestor(): ApiRequestor {
        return new ApiRequestor('token', 'https://rest.cleverreach.com/v3/', $this->httpClient, $this->requestFactory, $this->streamFactory);
    }
}
