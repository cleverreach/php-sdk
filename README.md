# CleverReach PHP SDK

[![Packagist Version](https://img.shields.io/packagist/v/cleverreach/php-sdk.svg)](https://packagist.org/packages/cleverreach/php-sdk)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-purple)](https://www.php.net)
[![License MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A lightweight PHP SDK for the [CleverReach REST API](https://developers.cleverreach.com/).
Built on PSR-18 / PSR-17 interfaces

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [OAuth 2.0 Flow](#oauth-20-flow)
- [Features](#features)
- [Typed Service API](#typed-service-api)
  - [Groups](#groups)
  - [Receivers (via group)](#receivers-via-group)
  - [Receiver lookup](#receiver-lookup)
- [Raw API Access](#raw-api-access)
- [Error Handling](#error-handling)
- [Advanced: Bring Your Own HTTP Client](#advanced-bring-your-own-http-client)
- [Development](#development)
- [License](#license)

---

## Requirements

| Requirement | Version       |
| ----------- | ------------- |
| PHP         | 8.2 or higher |
| Composer    | 2.x           |

You also need **one** PSR-18-compatible HTTP client in your project.
Popular choices:

```bash
# Guzzle
composer require guzzlehttp/guzzle

# Symfony HTTP Client
composer require symfony/http-client nyholm/psr7
```

The SDK auto-detects the available client via `php-http/discovery` – no additional configuration needed.

---

## Installation

```bash
composer require cleverreach/sdk-php
```

---

## Quick Start

Get up and running in under a minute:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use CleverReach\SDK\CleverReachClient;

// 1. Create a client with your API token
$client = new CleverReachClient('YOUR_API_TOKEN');

// 2. Fetch all groups using the typed service
$groups = $client->groups()->all();

foreach ($groups as $group) {
    echo $group->name . PHP_EOL;
}

// Alternatively, via raw request (returns arrays):
// $groupsArray = $client->request('GET', 'groups');
// echo $groupsArray[0]['name'];
```

That's it. The SDK handles authentication, JSON encoding/decoding, and error mapping automatically.

> Get your API Token via the "Test process now" button in the CleverReach backend under **My Account → Interfaces → REST API** OR use the built-in OAuth functions.

---

## OAuth 2.0 Flow

If you are building an app for multiple CleverReach customers, you need to use OAuth 2.0 instead of a rigid, static API token. The SDK provides an `OAuthHelper` to manage the complete authorization flow including strict CSRF validation (state checking) and automatic token-refresh routines.

### 1. Generating Login URL

```php
use CleverReach\SDK\Auth\OAuthHelper;

session_start();

$oauthHelper = new OAuthHelper('YOUR_CLIENT_ID', 'YOUR_CLIENT_SECRET', 'https://your-domain.com/callback');

// Generate secure state against CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$url = $oauthHelper->getAuthorizationUrl($state, ['receivers.read', 'groups.read']);

header('Location: ' . $url);
exit;
```

### 2. Handling the Callback

When CleverReach redirects the user back to your `redirect_uri` (e.g., `callback`), exchange the `code` for fully managed tokens. The SDK saves them per default on disk (`FileTokenStorage`), but you can inject a custom `TokenStorageInterface` to use Redis or Eloquent.

> **Pro Tip:** The SDK does NOT map tokens to users automatically (to remain agnostic). In a multi-user environment, implement the `TokenStorageInterface` to link the stored `Tokens` to the currently logged-in user in your database.

```php
use CleverReach\SDK\Auth\Exceptions\CleverReachAuthException;
use CleverReach\SDK\Auth\OAuthHelper;

session_start();

// Make sure to construct the helper with exactly your credentials
$oauthHelper = new OAuthHelper('YOUR_CLIENT_ID', 'YOUR_CLIENT_SECRET', 'https://your-domain.com/callback');

try {
    $expectedState = $_SESSION['oauth_state'] ?? '';
    $receivedState = $_GET['state'] ?? '';
    $code          = $_GET['code'] ?? '';

    // Exchanges the code and validates the returned state against the session value
    $tokens = $oauthHelper->exchangeCodeForToken($code, $receivedState, $expectedState);

    echo "Login success! Tokens cached.";
} catch (CleverReachAuthException $e) {
    die("Authorization failed: " . $e->getMessage());
}
```

### Custom Token Storage (Database/Redis)

For multi-tenant applications, you should write your own storage adapter by implementing the `TokenStorageInterface`. This allows you to persistently store and retrieve the tokens based on your system's user ID.

```php
use CleverReach\SDK\Auth\Storage\TokenStorageInterface;
use CleverReach\SDK\Auth\Tokens;

class MyDatabaseTokenStorage implements TokenStorageInterface {
    public function __construct(private int $userId) {}

    public function get(): ?Tokens {
        // SELECT * FROM oauth_tokens WHERE user_id = $this->userId
        // if found, return Tokens::fromArray($dbData);
        // else return null;
    }

    public function set(Tokens $tokens): void {
        // UPDATE/INSERT INTO oauth_tokens WHERE user_id = $this->userId
        // You can also access Scopes: $tokens->hasScope('receivers.read')
    }

    public function delete(): void {
        // DELETE FROM oauth_tokens WHERE user_id = $this->userId
    }
}

// Pass it to the Helper during setup
$storage = new MyDatabaseTokenStorage($_SESSION['user_id']);
$oauthHelper = new OAuthHelper('CLIENT_ID', 'SECRET', 'CALLBACK', $storage);
```

### 3. API Requests with OAuth

Hook the `OAuthHelper` into your client. It acts as a `TokenProvider` and will autonomously fetch or refresh access tokens prior to any endpoint requests.

```php
use CleverReach\SDK\CleverReachClient;

$client = new CleverReachClient(); // Leave token empty
$client->setTokenProvider($oauthHelper);

// SDK handles adding Bearer token.
// If the token is expired, SDK will automatically refresh & retry the API call.
$groups = $client->groups()->all();
```

### Revoking Tokens (Logout)

If a user uninstalls your app or logs out, you should actively revoke the token to invalidate it on the CleverReach servers:

```php
// Deletes the token locally and on the server
$oauthHelper->revokeToken($tokens->getAccessToken());
```

---

## Features

- **Zero boilerplate** – one class, one method
- **PSR-18 / PSR-17** – works with Guzzle, Symfony HTTP Client, or any compliant client
- **Auto-discovery** – no manual HTTP client setup required
- **Typed service API** – key endpoints covered by dedicated service methods with full IDE autocompletion and type-safe enums
- **Raw access** – every CleverReach REST endpoint reachable with `request()`

---

## Typed Service API

The SDK provides typed service classes for the most common endpoints. These are the **recommended** way to interact with CleverReach – your IDE will autocomplete parameters and the compiler will catch type errors.

### Groups

```php
use CleverReach\SDK\CleverReachClient;
use CleverReach\SDK\Enum\GroupSortField;
use CleverReach\SDK\Enum\SortOrder;

$client = new CleverReachClient('YOUR_API_TOKEN');

// Fetch all groups, sorted by last change descending
$groups = $client->groups()->all(
    order: GroupSortField::Changed,
    direction: SortOrder::Descending
);

foreach ($groups as $group) {
    echo $group->id . ': ' . $group->name . PHP_EOL;
}

// Fetch a single group by ID
$group = $client->groups()->get(123);
echo $group->name;
```

### Receivers (via group)

```php
use CleverReach\SDK\Enum\ReceiverSortField;
use CleverReach\SDK\Enum\ReceiverType;
use CleverReach\SDK\Enum\SortOrder;

$client = new CleverReachClient('YOUR_API_TOKEN');

// List active receivers in a group, sorted by email
$receivers = $client->groups()->getReceivers(
    groupId: 123,
    page: 0,
    pageSize: 100,
    type: ReceiverType::Active,
    orderBy: ReceiverSortField::Email,
    orderDirection: SortOrder::Ascending
);

foreach ($receivers as $receiver) {
    echo $receiver->email . PHP_EOL;
}

// Filter by specific emails or IDs
$receivers = $client->groups()->getReceivers(
    groupId: 123,
    emailList: ['jane@example.com', 'bob@example.com']
);
```

### Receiver lookup

```php
// By numeric ID
$receiver = $client->receivers()->get(381940);

// By email address
$receiver = $client->receivers()->get('jane@example.com');

echo $receiver->email . ' – active: ' . ($receiver->active ? 'yes' : 'no');
```

---

## Error Handling

All service methods and `request()` throw exceptions from `CleverReach\SDK\Exception\`:

| Exception                    | When                                                            |
| ---------------------------- | --------------------------------------------------------------- |
| `AuthenticationException`    | 401 – invalid or expired API token                              |
| `ValidationException`        | 400 – bad request (invalid payload or parameters)               |
| `ResourceNotFoundException`  | 404 – resource does not exist (e.g. invalid group ID)           |
| `RateLimitExceededException` | 429 – too many requests                                         |
| `MissingDependencyException` | No PSR-18 HTTP client found at construction time                |
| `CleverReachException`       | Any other API error (4xx/5xx), network failure, or invalid JSON |

All specific exceptions extend `CleverReachException`, so you can catch all errors with a single `catch` block, or handle specific cases:

```php
use CleverReach\SDK\Exception\AuthenticationException;
use CleverReach\SDK\Exception\RateLimitExceededException;
use CleverReach\SDK\Exception\CleverReachException;

try {
    $groups = $client->groups()->all();
} catch (AuthenticationException $e) {
    // Token invalid – refresh and retry
    echo 'Auth error: ' . $e->getMessage();
} catch (RateLimitExceededException $e) {
    // Too many requests - sleep and retry
    sleep(60);
} catch (CleverReachException $e) {
    // Network error, API error, etc.
    echo 'API error ' . $e->statusCode() . ': ' . $e->getMessage();
    echo 'Raw response body: ' . $e->responseBody();
}
```

---

## Raw API Access

All requests go through the single `request()` method:

```php
request(string $method, string $endpoint, array $query = [], ?array $json = null): array
```

| Parameter   | Type          | Description                                         |
| ----------- | ------------- | --------------------------------------------------- |
| `$method`   | `string`      | HTTP verb: `GET`, `POST`, `PUT`, `DELETE`           |
| `$endpoint` | `string`      | Path relative to `https://rest.cleverreach.com/v3/` |
| `$query`    | `array`       | URL query parameters (`null` values are ignored)    |
| `$json`     | `array\|null` | Request body, JSON-encoded automatically            |

Returns `array` – either an associative array (single object) or a list of associative arrays.

### Example: Custom Endpoint

If an endpoint is not yet covered by the typed services, you can easily call it directly:

```php
$response = $client->request(
    'POST',
    'some/custom/endpoint',
    ['query_param' => 'value'],
    ['json_key' => 'json_value']
);
```

---

### Full API Reference

The examples above cover the most common use cases. The CleverReach REST API offers many more endpoints – reports, forms, attributes, filters, orders, events, and more.

> **[→ CleverReach REST API Documentation](https://developers.cleverreach.com/api/)**
> Full endpoint reference, request/response schemas, and interactive Swagger UI.

---

## Advanced: Bring Your Own HTTP Client

By default the SDK uses `php-http/discovery` to find a PSR-18 client automatically.
You can inject your own implementations – ideal for DI containers or testing:

```php
use CleverReach\SDK\CleverReachClient;

$client = new CleverReachClient(
    apiToken:        'YOUR_API_TOKEN',
    baseUri:         'https://rest.cleverreach.com/v3/', // optional, this is the default
    httpClient:      $myPsr18Client,
    requestFactory:  $myPsr17RequestFactory,
    streamFactory:   $myPsr17StreamFactory,
);
```

### Example: Guzzle with explicit setup

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use CleverReach\SDK\CleverReachClient;

$factory = new HttpFactory();

$client = new CleverReachClient(
    apiToken:       'YOUR_API_TOKEN',
    httpClient:     new GuzzleClient(),
    requestFactory: $factory,
    streamFactory:  $factory,
);
```

---

## Development

```bash
# Install dependencies
composer install

# Syntax check
composer lint

# Code style check
composer cs-check

# Auto-fix code style
composer cs-fix

# Run test suite
composer test
```

---

## License

This project is licensed under the **MIT License**.
See the [LICENSE](LICENSE) file for details.
