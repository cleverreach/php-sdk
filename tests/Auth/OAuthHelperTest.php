<?php

declare(strict_types=1);

namespace CleverReach\Tests\Auth;

use CleverReach\SDK\Auth\Exceptions\CleverReachAuthException;
use CleverReach\SDK\Auth\OAuthHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @internal
 */
#[CoversClass(OAuthHelper::class)]
final class OAuthHelperTest extends TestCase
{
    public function testExchangeCodeForTokenThrowsOnEmptyStates(): void {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $helper = new OAuthHelper(
            'client_id',
            'client_secret',
            'https://example.com/callback',
            null,
            'https://rest.cleverreach.com/v3',
            $httpClient,
            $requestFactory,
            $streamFactory
        );

        $httpClient->expects(self::never())->method('sendRequest');

        $this->expectException(CleverReachAuthException::class);
        $this->expectExceptionMessage('Invalid state: state must not be empty.');

        $helper->exchangeCodeForToken('code123', '', '');
    }
}
