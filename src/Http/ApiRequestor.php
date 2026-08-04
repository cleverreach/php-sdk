<?php

declare(strict_types=1);

namespace CleverReach\SDK\Http;

use CleverReach\SDK\Auth\TokenProviderInterface;
use CleverReach\SDK\Exception\AuthenticationException;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\MissingDependencyException;
use CleverReach\SDK\Exception\RateLimitExceededException;
use CleverReach\SDK\Exception\ResourceNotFoundException;
use CleverReach\SDK\Exception\ValidationException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ApiRequestor implements ApiRequestorInterface
{
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;
    private readonly string $baseUri;
    private ?TokenProviderInterface $tokenProvider = null;

    public function __construct(
        private readonly string $apiToken = '',
        string $baseUri = 'https://rest.cleverreach.com/v3/',
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->baseUri = rtrim($baseUri, '/').'/';

        try {
            $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
            $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
            $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        } catch (\Throwable $e) {
            throw new MissingDependencyException(
                'Could not find a PSR-18 HTTP client or PSR-17 factory. '
                ."Please install a package like 'guzzlehttp/guzzle' or 'symfony/http-client' and 'nyholm/psr7', "
                .'or pass your own implementations to the constructor.',
                null,
                null,
                $e
            );
        }
    }

    /**
     * @param array<string, null|scalar> $query
     * @param null|array<string, mixed>  $json
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function request(
        string $method,
        string $uri,
        array $query = [],
        ?array $json = null
    ): array {
        return $this->doRequest($method, $uri, $query, $json, false);
    }

    public function setTokenProvider(TokenProviderInterface $tokenProvider): void {
        $this->tokenProvider = $tokenProvider;
    }

    /**
     * @param array<string, null|scalar> $query
     * @param null|array<string, mixed>  $json
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function doRequest(
        string $method,
        string $uri,
        array $query,
        ?array $json,
        bool $isRetrying
    ): array {
        if ($this->tokenProvider === null && $this->apiToken === '') {
            throw new AuthenticationException(
                'No authentication token provided. You must either pass a static API token in the constructor or use setTokenProvider().',
                0
            );
        }

        try {
            $request = $this->createRequest($method, $uri, $query, $json, $isRetrying);
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $rawBody = (string) $response->getBody();

            // If we get an Unauthorized response and we use a TokenProvider (OAuth),
            // the token might have been invalidated server-side before its expiry date.
            // Force a hard refresh and retry exactly once.
            if ($statusCode === 401 && !$isRetrying && $this->tokenProvider !== null) {
                return $this->doRequest($method, $uri, $query, $json, true);
            }

            if ($statusCode === 401) {
                throw new AuthenticationException(
                    $this->buildErrorMessage($statusCode, $rawBody, 'CleverReach API request failed.'),
                    $statusCode,
                    $rawBody
                );
            }

            if ($statusCode === 400) {
                throw new ValidationException(
                    $this->buildErrorMessage($statusCode, $rawBody, 'CleverReach API request failed due to validation errors.'),
                    $statusCode,
                    $rawBody
                );
            }

            if ($statusCode === 404) {
                throw new ResourceNotFoundException(
                    $this->buildErrorMessage($statusCode, $rawBody, 'CleverReach API resource not found.'),
                    $statusCode,
                    $rawBody
                );
            }

            if ($statusCode === 429) {
                throw new RateLimitExceededException(
                    $this->buildErrorMessage($statusCode, $rawBody, 'CleverReach API rate limit exceeded.'),
                    $statusCode,
                    $rawBody
                );
            }

            if ($statusCode >= 400) {
                throw new CleverReachException(
                    $this->buildErrorMessage($statusCode, $rawBody, 'CleverReach API request failed.'),
                    $statusCode,
                    $rawBody
                );
            }

            if ($rawBody === '') {
                return [];
            }

            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw new CleverReachException('Unexpected response format from CleverReach API.');
            }

            return $decoded;
        } catch (ClientExceptionInterface $exception) {
            throw new CleverReachException('HTTP communication with CleverReach failed.', null, null, $exception);
        } catch (\JsonException $exception) {
            throw new CleverReachException('Failed to decode CleverReach API response JSON.', null, null, $exception);
        }
    }

    /**
     * @param array<string, null|scalar> $query
     * @param null|array<string, mixed>  $json
     */
    private function createRequest(string $method, string $uri, array $query, ?array $json, bool $forceTokenRefresh): RequestInterface {
        $url = $this->baseUri.ltrim($uri, '/');
        $cleanQuery = array_filter($query, static fn (mixed $value): bool => $value !== null);

        if ($cleanQuery !== []) {
            $url .= '?'.http_build_query($cleanQuery);
        }

        $request = $this->requestFactory
            ->createRequest($method, $url)
            ->withHeader('Accept', 'application/json')
        ;

        if ($this->tokenProvider !== null) {
            $request = $request->withHeader('Authorization', 'Bearer '.$this->tokenProvider->getAccessToken($forceTokenRefresh));
        } elseif ($this->apiToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer '.$this->apiToken);
        }

        if ($json === null) {
            return $request;
        }

        try {
            $body = json_encode($json, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new CleverReachException('Failed to encode CleverReach API request JSON.', null, null, $exception);
        }

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body))
        ;
    }

    private function buildErrorMessage(?int $statusCode, ?string $responseBody, string $fallback): string {
        if ($responseBody !== null && $responseBody !== '') {
            $decoded = json_decode($responseBody, true);
            if (is_array($decoded)) {
                $message = $decoded['error'] ?? $decoded['message'] ?? $decoded['error_description'] ?? null;
                if (is_string($message) && $message !== '') {
                    return $message;
                }
            }
        }

        if ($statusCode !== null) {
            return sprintf('CleverReach API request failed with status %d.', $statusCode);
        }

        return $fallback;
    }
}
