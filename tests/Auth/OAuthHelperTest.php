<?php

declare(strict_types=1);

namespace CleverReach\Tests\Auth;

use CleverReach\SDK\Auth\Exceptions\CleverReachAuthException;
use CleverReach\SDK\Auth\OAuthHelper;
use CleverReach\SDK\Auth\Storage\TokenStorageInterface;
use CleverReach\SDK\Auth\Tokens;
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
#[CoversClass(OAuthHelper::class)]
final class OAuthHelperTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;
    private MockObject&RequestFactoryInterface $requestFactory;
    private MockObject&StreamFactoryInterface $streamFactory;
    private MockObject&TokenStorageInterface $storage;
    private MockObject&RequestInterface $request;
    private MockObject&ResponseInterface $response;
    private MockObject&StreamInterface $responseBody;

    private OAuthHelper $helper;

    protected function setUp(): void {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->storage = $this->createMock(TokenStorageInterface::class);

        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseBody = $this->createMock(StreamInterface::class);

        $this->helper = new OAuthHelper(
            'client_id',
            'client_secret',
            'https://example.com/callback',
            $this->storage,
            'https://rest.cleverreach.com/oauth',
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory
        );
    }

    public function testGetAuthorizationUrlThrowsOnEmptyState(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('State must not be null or empty.');

        $this->helper->getAuthorizationUrl('');
    }

    public function testGetAuthorizationUrlBuildsCorrectUrl(): void {
        $url = $this->helper->getAuthorizationUrl('my_state_123', ['receivers:read']);

        self::assertSame(
            'https://rest.cleverreach.com/oauth/authorize.php?client_id=client_id&redirect_uri=https%3A%2F%2Fexample.com%2Fcallback&response_type=code&grant=basic&state=my_state_123&scope=receivers%3Aread',
            $url
        );
    }

    public function testExchangeCodeForTokenThrowsOnEmptyStates(): void {
        $this->httpClient->expects(self::never())->method('sendRequest');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('Invalid state: state must not be empty.');

        $this->helper->exchangeCodeForToken('code123', ' ', ' ');
    }

    public function testExchangeCodeForTokenThrowsOnStateMismatch(): void {
        $this->httpClient->expects(self::never())->method('sendRequest');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('Invalid state: state mismatch.');

        $this->helper->exchangeCodeForToken('code123', 'state1', 'state2');
    }

    public function testExchangeCodeForTokenPerformsTokenRequestAndSaves(): void {
        $this->setupTokenRequestMock(200, '{"access_token": "acc_123", "refresh_token": "ref_456"}');

        $this->storage->expects(self::once())->method('set')->with(self::isInstanceOf(Tokens::class));

        $tokens = $this->helper->exchangeCodeForToken('code123', 'state_match', 'state_match');

        self::assertSame('acc_123', $tokens->getAccessToken());
        self::assertSame('ref_456', $tokens->getRefreshToken());
    }

    public function testRefreshAccessTokenThrowsIfNoTokenAvailable(): void {
        $this->storage->method('get')->willReturn(null);

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('No refresh token available.');

        $this->helper->refreshAccessToken();
    }

    public function testRefreshAccessTokenPerformsRequestAndSaves(): void {
        $this->setupTokenRequestMock(200, '{"access_token": "acc_new", "refresh_token": "ref_new"}');

        $this->storage->expects(self::once())->method('set')->with(self::isInstanceOf(Tokens::class));

        $tokens = $this->helper->refreshAccessToken('old_refresh_token');

        self::assertSame('acc_new', $tokens->getAccessToken());
    }

    public function testDoTokenRequestEvictsTokensOnRefreshError4xx(): void {
        $this->setupTokenRequestMock(400, '{"error": "invalid_grant"}');

        // It should call clearStoredTokens (delete) because grant_type=refresh_token
        $this->storage->expects(self::once())->method('delete');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('OAuth request failed: invalid_grant');

        $this->helper->refreshAccessToken('bad_refresh_token');
    }

    public function testDoTokenRequestThrowsOnInvalidJsonWithFallback(): void {
        $this->setupTokenRequestMock(502, '<html>Bad Gateway</html>');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('OAuth request failed with HTTP 502 and non-JSON body.');

        // exchangeCode triggers doTokenRequest
        $this->helper->exchangeCodeForToken('code', 'st', 'st');
    }

    public function testDoTokenRequestThrowsIfAccessTokenMissing(): void {
        $this->setupTokenRequestMock(200, '{"foo": "bar"}');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('Invalid API response: missing access_token.');

        $this->helper->exchangeCodeForToken('code', 'st', 'st');
    }

    public function testDoTokenRequestScopesAsArray(): void {
        $this->setupTokenRequestMock(200, '{"access_token": "acc", "scope": ["receivers:read"]}');

        $tokens = $this->helper->exchangeCodeForToken('code', 'st', 'st');
        self::assertSame(['receivers:read'], $tokens->getScopes());
    }

    public function testDoTokenRequestScopesAsString(): void {
        $this->setupTokenRequestMock(200, '{"access_token": "acc", "scope": "receivers:read groups:manage"}');

        $tokens = $this->helper->exchangeCodeForToken('code', 'st', 'st');
        self::assertSame(['receivers:read', 'groups:manage'], $tokens->getScopes());
    }

    public function testDoTokenRequestJsonExceptionOn200(): void {
        $this->setupTokenRequestMock(200, '{"broken": ');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('Failed to decode CleverReach OAuth response.');

        $this->helper->exchangeCodeForToken('code', 'st', 'st');
    }

    public function testDoTokenRequestThrowsFallbackMessageIfErrorStringMissing(): void {
        $this->setupTokenRequestMock(403, '{"unknown_key": "forbidden"}');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('OAuth request failed: API request failed');

        $this->helper->exchangeCodeForToken('code', 'st', 'st');
    }

    public function testDoTokenRequestWrapsClientException(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->request->method('withBody')->willReturnSelf();

        $exception = new class('Network error') extends \RuntimeException implements ClientExceptionInterface {};
        $this->httpClient->method('sendRequest')->willThrowException($exception);

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('HTTP communication during OAuth failed.');

        $this->helper->exchangeCodeForToken('code', 'st', 'st');
    }

    public function testGetAccessTokenThrowsIfNoTokenInStorage(): void {
        $this->storage->method('get')->willReturn(null);

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('No access token available. Authorization required.');

        $this->helper->getAccessToken();
    }

    public function testGetAccessTokenReturnsTokenIfNotExpired(): void {
        $tokens = new Tokens('valid_acc_token', null, time() + 3600);
        $this->storage->method('get')->willReturn($tokens);

        // No refresh request should be made
        $this->httpClient->expects(self::never())->method('sendRequest');

        $token = $this->helper->getAccessToken();
        self::assertSame('valid_acc_token', $token);
    }

    public function testGetAccessTokenRefreshesIfExpired(): void {
        // Expired token
        $tokens = new Tokens('expired_acc_token', 'refresh_token', time() - 3600);
        $this->storage->method('get')->willReturn($tokens);

        $this->setupTokenRequestMock(200, '{"access_token": "fresh_acc_token"}');

        $token = $this->helper->getAccessToken();
        self::assertSame('fresh_acc_token', $token);
    }

    public function testRevokeTokenSendsDeleteRequestAndClearsStorage(): void {
        $this->requestFactory->expects(self::once())
            ->method('createRequest')
            ->with('DELETE', 'https://rest.cleverreach.com/oauth/token')
            ->willReturn($this->request)
        ;

        $this->request->expects(self::once())
            ->method('withHeader')
            ->with('Authorization', 'Bearer dummy_token')
            ->willReturnSelf()
        ;

        $this->httpClient->expects(self::once())
            ->method('sendRequest')
            ->with($this->request)
        ;

        $this->storage->expects(self::once())->method('delete');

        $this->helper->revokeToken('dummy_token');
    }

    public function testRevokeTokenWrapsClientException(): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();

        $exception = new class('Network error') extends \RuntimeException implements ClientExceptionInterface {};
        $this->httpClient->method('sendRequest')->willThrowException($exception);

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('HTTP communication during token revocation failed.');

        $this->helper->revokeToken('dummy_token');
    }

    private function setupTokenRequestMock(int $statusCode, string $responseBodyString): void {
        $this->requestFactory->method('createRequest')->willReturn($this->request);
        $this->request->method('withHeader')->willReturnSelf();
        $this->request->method('withBody')->willReturnSelf();

        $this->httpClient->expects(self::once())
            ->method('sendRequest')
            ->with($this->request)
            ->willReturn($this->response)
        ;

        $this->response->method('getStatusCode')->willReturn($statusCode);
        $this->response->method('getBody')->willReturn($this->responseBody);

        $this->responseBody->method('__toString')->willReturn($responseBodyString);
    }
}
