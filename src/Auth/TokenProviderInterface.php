<?php

declare(strict_types=1);

namespace CleverReach\SDK\Auth;

interface TokenProviderInterface
{
    /**
     * Returns a valid access token. Should automatically fetch or refresh
     * the token if it is expired or missing.
     *
     * @param bool $forceRefresh If true, forces a new token to be obtained, bypassing expiry checks
     *
     * @return string The access token
     */
    public function getAccessToken(bool $forceRefresh = false): string;
}
