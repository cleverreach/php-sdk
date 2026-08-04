<?php

declare(strict_types=1);

namespace CleverReach\SDK\Auth;

use CleverReach\SDK\Auth\Exceptions\CleverReachAuthException;
use CleverReach\SDK\Auth\Storage\MemoryTokenStorage;
use CleverReach\SDK\Auth\Storage\TokenStorageInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class OAuthHelper implements TokenProviderInterface
{
    private readonly TokenStorageInterface $storage;
    private readonly string $authBaseUrl;
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        ?TokenStorageInterface $storage = null,
        string $baseUri = 'https://rest.cleverreach.com/v3',
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->storage = $storage ?? new MemoryTokenStorage();

        $host = parse_url($baseUri, PHP_URL_HOST) ?? 'rest.cleverreach.com';
        $scheme = parse_url($baseUri, PHP_URL_SCHEME) ?? 'https';
        $this->authBaseUrl = $scheme.'://'.$host.'/oauth';

        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * @param array<int, string> $scopes
     */
    public function getAuthorizationUrl(?string $state = null, array $scopes = []): string {
        if ($state === null || trim($state) === '') {
            throw new \InvalidArgumentException('State must not be null or empty.');
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'grant' => 'basic',
            'state' => $state,
        ];

        if ($scopes !== []) {
            $params['scope'] = implode(' ', $scopes);
        }

        return $this->authBaseUrl.'/authorize.php?'.http_build_query($params);
    }

    /**
     * Exchanges the authorization code received from the callback for an access token.
     * The resulting token is automatically stored in the configured token storage.
     */
    public function exchangeCodeForToken(string $code, string $receivedState, string $expectedState): Tokens {
        $expectedState = trim($expectedState);
        $receivedState = trim($receivedState);

        if ($expectedState === '' || $receivedState === '') {
            throw new CleverReachAuthException('Invalid state: state must not be empty.');
        }

        if (!hash_equals($expectedState, $receivedState)) {
            throw new CleverReachAuthException('Invalid state: state mismatch.');
        }

        return $this->doTokenRequest([
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);
    }

    /**
     * Refreshes the access token using the stored refresh token, or a specific provided token.
     * The new token is automatically stored in the configured token storage.
     *
     * @throws CleverReachAuthException If no refresh token is available
     */
    public function refreshAccessToken(?string $refreshToken = null): Tokens {
        $tokenToUse = $refreshToken ?? $this->storage->get()?->getRefreshToken();

        if ($tokenToUse === null) {
            throw new CleverReachAuthException('No refresh token available.');
        }

        return $this->doTokenRequest([
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $tokenToUse,
        ]);
    }

    /**
     * Internal generic method to fulfill TokenProviderInterface.
     * Always ensures returning a valid access token (refreshes if necessary).
     *
     * @param bool $forceRefresh If true, skips the expiry check and forces a refresh via API
     */
    public function getAccessToken(bool $forceRefresh = false): string {
        $tokens = $this->storage->get();

        if ($tokens === null) {
            throw new CleverReachAuthException('No access token available. Authorization required.');
        }

        if ($forceRefresh || $tokens->isExpired()) {
            $tokens = $this->refreshAccessToken($tokens->getRefreshToken());
        }

        return $tokens->getAccessToken();
    }

    public function clearStoredTokens(): void {
        $this->storage->delete();
    }

    /**
     * Revokes a specific token at the CleverReach backend.
     * Use this when logging out users.
     */
    public function revokeToken(string $token): void {
        $request = $this->requestFactory
            ->createRequest('DELETE', $this->authBaseUrl.'/token')
            ->withHeader('Authorization', 'Bearer '.$token)
        ;

        try {
            $this->httpClient->sendRequest($request);
            $this->clearStoredTokens();
        } catch (ClientExceptionInterface $e) {
            throw new CleverReachAuthException('HTTP communication during token revocation failed.', null, null, $e);
        }
    }

    /**
     * Executes an OAuth token request directly using the embedded PSR-18 client,
     * so that the logic remains strictly separated from `ApiRequestor`.
     *
     * @param array<string, mixed> $bodyParams
     */
    private function doTokenRequest(array $bodyParams): Tokens {
        $request = $this->requestFactory
            ->createRequest('POST', $this->authBaseUrl.'/token.php')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream(http_build_query($bodyParams)))
        ;

        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $rawBody = (string) $response->getBody();

            if ($statusCode >= 400 && $statusCode < 500 && ($bodyParams['grant_type'] ?? '') === 'refresh_token') {
                // If a refresh request fails with a 4xx error (e.g. invalid grant),
                // the refresh token is permanently broken. Clear it to avoid infinite loops.
                $this->clearStoredTokens();
            }

            try {
                $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                if ($statusCode < 200 || $statusCode >= 300) {
                    throw new CleverReachAuthException('OAuth request failed with HTTP '.$statusCode.' and non-JSON body.');
                }

                throw $e;
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                if (is_array($decoded)) {
                    $errorMsg = $decoded['error_description'] ?? $decoded['error'] ?? 'API request failed';
                } else {
                    $errorMsg = 'OAuth request failed with HTTP '.$statusCode;
                }

                throw new CleverReachAuthException('OAuth request failed: '.$errorMsg);
            }

            if (!is_array($decoded) || !isset($decoded['access_token'])) {
                throw new CleverReachAuthException('Invalid API response: missing access_token.');
            }

            $scopes = [];
            if (isset($decoded['scope'])) {
                if (is_array($decoded['scope'])) {
                    $scopes = $decoded['scope'];
                } elseif (is_string($decoded['scope'])) {
                    $scopes = explode(' ', $decoded['scope']);
                }
            }

            $tokens = new Tokens(
                (string) $decoded['access_token'],
                isset($decoded['refresh_token']) ? (string) $decoded['refresh_token'] : null,
                isset($decoded['expires_in']) ? time() + (int) $decoded['expires_in'] : null,
                $scopes
            );

            $this->storage->set($tokens);

            return $tokens;
        } catch (\JsonException $e) {
            throw new CleverReachAuthException('Failed to decode CleverReach OAuth response.', null, null, $e);
        } catch (ClientExceptionInterface $e) {
            throw new CleverReachAuthException('HTTP communication during OAuth failed.', null, null, $e);
        }
    }
}
