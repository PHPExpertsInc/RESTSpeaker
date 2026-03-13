# RESTSpeaker - App Layout

## File Tree

```text
src/
├── (1) RESTSpeaker.php       — Core entry point, orchestrates HTTP methods, auth injection, and response parsing
├── (2) HTTPSpeaker.php       — Guzzle abstraction layer, handles raw HTTP semantics, middleware, and Cuzzle logging
├── (3) RESTAuthDriver.php    — Interface contract for all authentication strategies
├── (4) RESTAuth.php          — Abstract base class for Token/Key-based auth modes (OAuth2, Passkey, X-API-Key, etc.)
├── (5) BasicAuth.php         — HTTP Basic Authentication strategy implementation
└── (6) NoAuth.php            — Null object pattern implementation for unauthenticated requests

```

---

## Module Breakdown

### **Core Client Subsystem** (Files 1–2)

**Purpose**: Provide a highly ergonomic, boilerplate-free interface over Guzzle while abstracting away the underlying transfer protocol layer and automating common REST tasks (like JSON decoding).

#### **(1) RESTSpeaker.php**

* **Primary Facade**: The main public-facing class utilized for API consumption. Provides direct methods (`get`, `post`, `put`, `patch`, etc.) via magic `__call` routing.
* **Composition Engine**: Leverages composition to expose Guzzle's power without inheriting its rigid internal state.
* **Auto-Deserialization**: Automatically detects `application/json` responses and decodes them natively, falling back to raw strings for other content types.
* **Auth Orchestration**: Automatically requests Guzzle auth options from the injected `RESTAuthDriver` and passes them to the HTTP layer.

#### **(2) HTTPSpeaker.php**

* **Guzzle Bridge**: Acts as the exact bridge between the high-level `RESTSpeaker` and the underlying `GuzzleHttp\Client`.
* **Options Merging**: Uses `mergeGuzzleOptions()` to safely combine user-provided HTTP options, default headers (like `User-Agent`), and injected authentication parameters.
* **Middleware Management**: Manages the `HandlerStack`. Automatically detects and integrates `Namshi\Cuzzle` for cURL logging and debugging if available.
* **State Tracking**: Holds the `$lastResponse` for inspection of raw status codes and headers.

---

### **Authentication Subsystem** (Files 3–6)

**Purpose**: Decouple authentication logic from HTTP execution using the Strategy Pattern, allowing developers to switch between Auth modes without touching the core client.

#### **(3) RESTAuthDriver.php**

* **The Contract**: Ensures every auth strategy can associate with the `RESTSpeaker` instance and generate valid Guzzle HTTP options.

#### **(4) RESTAuth.php**

* **Base Strategy Manager**: Handles common token-based authentication types out-of-the-box (`OAuth2Token`, `Passkey`, `XAPIToken`, `CustomAuth`).
* **Dynamic Routing**: Uses the `$authMode` property to dynamically route `generateGuzzleAuthOptions()` to specific handlers like `generateXAPITokenOptions()`.

#### **(5) BasicAuth.php**

* **Implementation**: Handles standard HTTP Basic Authentication (Username/Password), directly formatting the credentials into Guzzle's required `['auth' => [$user, $pass]]` array structure.

#### **(6) NoAuth.php**

* **Null Object Pattern**: Safely returns empty configuration arrays for public, unauthenticated API endpoints, preventing null-reference errors in the core client.

---

## LLM & Developer API Quickstart

**RESTSpeaker** is designed to eliminate boilerplate. It operates as a drop-in Guzzle alternative that provides automatic JSON parsing, PHP 7.4+ type safety, and built-in authentication strategies out of the box.

### 1. Initialization & Authentication

Every `RESTSpeaker` instance requires an authentication driver on instantiation.

* **Unauthenticated Requests:**
```php
$api = new RESTSpeaker(new NoAuth(), '[https://api.example.com](https://api.example.com)');

```


* **Custom / Bearer Token Authentication:**
```php
class BearerAuth extends RESTAuth {
    protected function generateCustomAuthOptions(): array {
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token
            ]
        ];
    }
}
$api = new RESTSpeaker(new BearerAuth('token123'), '[https://api.example.com](https://api.example.com)');

```



### 2. Common HTTP Methods

Standard HTTP verbs are routed automatically. If the client expects JSON, it decodes the response natively so you can access properties immediately.

* **GET Request:**
```php
$user = $api->get('/users/123');
echo $user->name; // Automatically parsed from JSON response

```


* **POST Request:**
```php
$newUser = $api->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

```



### 3. Content-Type Flexibility

The default expected format is `application/json`. You can easily adjust this to handle binary streams, PDFs, XML, or other payloads.

```php
$api->setContentType('application/pdf');
$pdf = $api->get('/reports/monthly.pdf');

```

### 4. Raw HTTP Fallback (Pure Guzzle + Auto-Auth)

If you need to bypass the automatic JSON decoding and interact with the raw Guzzle `Response` object (e.g., for multipart uploads, streaming, or custom headers), you can access the underlying `HTTPSpeaker` directly via the `$api->http` property.

To maintain the automated authentication layer, you can seamlessly pull the auth options from your configured strategy:

```php
// 1. Fetch the active authentication options
$authOptions = $api->getAuthStrat()->generateGuzzleAuthOptions();

// 2. Make a raw Guzzle call via $api->http, injecting the auth state
/** @var \GuzzleHttp\Psr7\Response $response */
$response = $api->http->post('/upload', array_merge_recursive([
    'multipart' => [
        [
            'name'     => 'document',
            'contents' => fopen('/path/to/file.pdf', 'r')
        ]
    ]
], $authOptions));

// 3. Interact directly with the PSR-7 response
$statusCode = $response->getStatusCode();
$rawBody = (string) $response->getBody();

```

*(Note: If you just need the raw PSR-7 response object after a standard `$api->get()` or `$api->post()` call, you can also use `$api->getLastResponse()`.)*

---

## Data Flow Example: One API Request

```text
Application
  ├─ Instantiation
  │    ├─ Create Auth Strategy: $auth = new OAuth2Auth(...)
  │    └─ Create Client: $api = new RESTSpeaker($auth, '[https://api.example.com](https://api.example.com)')
  │
  └─ Execution: $api->get('/users/123')
       ├─ RESTSpeaker::__call('get', ['/users/123'])
       │    ├─ RESTAuthDriver::generateGuzzleAuthOptions()     // Fetches Auth headers/tokens
       │    ├─ HTTPSpeaker::mergeGuzzleOptions()               // Injects Auth + Content-Type headers
       │    └─ HTTPSpeaker::__call('get', [...])               // Routes to Guzzle
       │         └─ GuzzleHttp\Client::request()               // Pure I/O
       │
       ├─ RESTSpeaker (Response Parsing)
       │    ├─ Check Content-Type (e.g., application/json)
       │    ├─ json_decode($response->getBody())
       │    └─ validate json_last_error()
       │
       └─ Returns Native PHP Object/Array (or raw string)

```

---

## Critical Design Principles

1. **Composition Over Inheritance**
`RESTSpeaker` is an isolated wrapper around Guzzle. By utilizing magic `__call` methods and the `ClientInterface`, it perfectly mimics a Guzzle client without the brittle side-effects of extending `GuzzleHttp\Client` directly.
2. **Zero Boilerplate & Developer Ergonomics**
Designed to eliminate the tedious 30+ lines of setup usually required for authenticated HTTP calls. JSON encoding/decoding, header management, and token injection happen invisibly.
3. **Modern PHP Type Safety**
Built for PHP 7.4+, the architecture enforces strict types (`declare(strict_types=1)`), typed properties (`protected string $contentType`), and nullable returns, greatly enhancing IDE static analysis and autocomplete.
4. **Strategy Pattern for Authentication**
Authentication is completely abstracted into interchangeable classes implementing `RESTAuthDriver`. This allows users to seamlessly swap from `NoAuth` in a sandbox to `OAuth2Auth` in production without altering the core API execution logic.
5. **Ecosystem Synergy (SimpleDTO Integration)**
The library's automatic JSON decoding is intentionally designed to feed directly into strongly-typed Data Transfer Objects (like `phpexperts/simple-dto`). This turns standard, untyped API arrays into validated, immutable objects with full IDE support.
