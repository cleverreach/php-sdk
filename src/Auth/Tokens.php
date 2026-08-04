<?php

declare(strict_types=1);

namespace CleverReach\SDK\Auth;

final class Tokens
{
    /**
     * @param array<int, string> $scopes
     */
    public function __construct(
        private readonly string $accessToken,
        private readonly ?string $refreshToken = null,
        private readonly ?int $expiresAt = null,
        private readonly array $scopes = []
    ) {
        if ($accessToken === '') {
            throw new \InvalidArgumentException('access_token must not be empty');
        }
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string {
        return $this->refreshToken;
    }

    public function getExpiresAt(): ?int {
        return $this->expiresAt;
    }

    /**
     * @return array<int, string>
     */
    public function getScopes(): array {
        return $this->scopes;
    }

    /**
     * Checks if the token possesses a specific scope.
     */
    public function hasScope(string $scope): bool {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Checks if the token is expired or will expire within the given margin.
     *
     * @param int $marginSeconds Safety margin in seconds
     */
    public function isExpired(int $marginSeconds = 60): bool {
        if ($this->expiresAt === null) {
            return false;
        }

        return time() >= ($this->expiresAt - $marginSeconds);
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?int, scopes: array<int, string>}
     */
    public function toArray(): array {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt,
            'scopes' => $this->scopes,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self {
        if (!isset($data['access_token']) || !is_string($data['access_token']) || trim($data['access_token']) === '') {
            throw new \InvalidArgumentException('access_token is missing, not a string, or empty');
        }

        if (isset($data['refresh_token']) && !is_string($data['refresh_token'])) {
            throw new \InvalidArgumentException('refresh_token must be a string or null');
        }

        if (isset($data['expires_at']) && !is_int($data['expires_at'])) {
            throw new \InvalidArgumentException('expires_at must be an integer or null');
        }

        $scopes = [];
        if (isset($data['scopes']) && is_array($data['scopes'])) {
            $scopes = array_filter($data['scopes'], static fn ($scope) => is_string($scope));
        }

        return new self(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            $data['expires_at'] ?? null,
            array_values($scopes)
        );
    }
}
