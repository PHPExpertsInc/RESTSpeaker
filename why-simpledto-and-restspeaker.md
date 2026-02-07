# Why Choose PHP Experts' RESTSpeaker + SimpleDTO

## Stop Writing Boilerplate: How RESTSpeaker + SimpleDTO Changed PHP API Development

**TL;DR:** Building API clients in PHP doesn't have to mean writing hundreds of lines of authentication, validation, and deserialization code. The RESTSpeaker + SimpleDTO ecosystem reduces SDK development time by 60-70% while producing more maintainable, type-safe code.

---

## The Problem: API Clients Are Tedious

If you've ever built a PHP API client, you know the drill:

```php
// Step 1: Handle OAuth2 authentication manually
$tokenClient = new GuzzleHttp\Client();
$tokenResponse = $tokenClient->post('https://auth.example.com/token', [
    'form_params' => [
        'grant_type' => 'password',
        'username' => 'user@example.com',
        'password' => 'secret',
        'client_id' => 'my-client',
        'client_secret' => 'my-secret'
    ]
]);
$token = json_decode($tokenResponse->getBody(), true)['access_token'];

// Step 2: Make the actual API call
$client = new GuzzleHttp\Client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ]
]);
$response = $client->get('/users/123');

// Step 3: Manually deserialize and validate
$data = json_decode($response->getBody(), true);
if (!isset($data['name'], $data['email'])) {
    throw new InvalidArgumentException('Missing required fields');
}
if (!is_string($data['name']) || !is_string($data['email'])) {
    throw new TypeError('Invalid field types');
}

// Step 4: Finally use the data
echo $data['name'];
```

That's **over 30 lines** just to make a simple authenticated API call. And we haven't even talked about:
- Token refresh logic
- Error handling
- Type safety
- IDE autocomplete
- Validating API contract changes

For every endpoint you support, you're writing this same boilerplate over and over.

## The Solution: An Integrated Ecosystem

What if you could reduce that to just **5 lines**?

```php
$auth = new OAuth2Auth([
    'username' => 'user@example.com',
    'password' => 'secret',
    'client_id' => 'my-client',
    'client_secret' => 'my-secret',
    'token_url' => 'https://api.example.com/oauth/token'
]);

$api = new RESTSpeaker($auth, 'https://api.example.com');
$user = $api->get('/users/123'); // Returns a validated UserDTO
echo $user->name; // Full IDE autocomplete
```

That's the promise of **phpexperts/rest-speaker** and **phpexperts/simple-dto**. Let me show you why this matters.

---

## Part 1: RESTSpeaker Handles the HTTP Layer

### Authentication Made Simple

RESTSpeaker comes with built-in authentication strategies:

**No Authentication:**
```php
$api = new RESTSpeaker(new NoAuth(), 'https://api.example.com');
```

**OAuth2 (Password Grant):**
```php
$auth = new OAuth2Auth([
    'username' => 'user@example.com',
    'password' => 'secret',
    'client_id' => 'my-client',
    'client_secret' => 'my-secret',
    'token_url' => 'https://auth.example.com/token'
]);
$api = new RESTSpeaker($auth, 'https://api.example.com');
```

**Custom Authentication:**
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
$api = new RESTSpeaker(new BearerAuth('my-token'), 'https://api.example.com');
```

The library handles token acquisition, refresh, and injection automatically. You never think about auth again.

### Modern PHP Type Safety

RESTSpeaker fully embraces PHP 7.4+ features:

- **Typed properties** for better IDE support
- **Strict types** (`declare(strict_types=1)`) throughout
- **Nullable types** with `?Type` syntax
- **Self-documenting code** through type declarations

```php
protected string $contentType = 'application/json';
protected ?Response $lastResponse = null;
public HTTPSpeaker $http;
```

This means fewer runtime errors and better refactoring support in modern IDEs.

### Guzzle-Compatible, But Better

Under the hood, RESTSpeaker uses Guzzle, so you get:
- Full access to Guzzle's middleware system
- PSR-7/PSR-18 compatibility
- Proven reliability
- Optional cURL logging via Cuzzle integration

But you write **far less code** to accomplish the same tasks.

---

## Part 2: SimpleDTO Makes Responses Type-Safe

Here's where things get really interesting. Instead of working with arrays or `stdClass` objects, you get **validated, immutable Data Transfer Objects**.

### The Traditional Approach (Painful)

```php
class UserResponse {
    private string $name;
    private string $email;
    private ?string $phone;
    
    public function __construct(string $name, string $email, ?string $phone = null) {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
    }
    
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
}

// Usage
$data = json_decode($response->getBody(), true);
$user = new UserResponse($data['name'], $data['email'], $data['phone'] ?? null);
```

**That's ~20 lines for a simple 3-field object.** Scale this to a real API with dozens of endpoints and you're looking at thousands of lines of boilerplate.

### The SimpleDTO Approach (Elegant)

```php
/**
 * @property-read string $name
 * @property-read string $email
 * @property-read string|null $phone
 */
class UserDTO extends SimpleDTO
{
}

// Usage
$user = new UserDTO(json_decode($response->getBody(), true));
echo $user->name; // Full IDE autocomplete
```

**That's 7 lines.** And you get:
- ✅ Automatic type validation
- ✅ Immutability by default
- ✅ IDE autocomplete
- ✅ JSON serialization built-in
- ✅ Nested DTO support
- ✅ Carbon date auto-conversion

### Advanced Validation

SimpleDTO lets you add custom validation logic:

```php
/**
 * @property-read string   $status
 * @property-read string   $result
 * @property-read string[] $flags
 */
final class EmailValidationDTO extends SimpleDTO
{
    public const RESULT_VALID = 'valid';
    public const RESULT_INVALID = 'invalid';
    public const RESULT_DISPOSABLE = 'disposable';
    
    public const RESULTS = [
        self::RESULT_VALID,
        self::RESULT_INVALID,
        self::RESULT_DISPOSABLE,
    ];

    protected function extraValidation(array $input)
    {
        if (!in_array($input['result'], self::RESULTS, true)) {
            throw new \InvalidArgumentException(
                "Invalid result: '{$input['result']}'."
            );
        }
    }
}
```

Now when the API returns an unexpected value, **you get a clear error message** instead of silent bugs downstream.

---

## Part 3: The Power of Integration

Here's where RESTSpeaker and SimpleDTO truly shine together. Let's look at a real production API client.

### Case Study: NeverBounce Email Validation SDK

The [NeverBounce API client](https://github.com/PHPExpertsInc/NeverBounce) demonstrates the full power of this ecosystem:

```php
class NeverBounceClient
{
    protected RESTSpeaker $api;

    public static function build(): self
    {
        $restAuth = new RestAuth(RestAuth::AUTH_MODE_PASSKEY);
        $client = new RESTSpeaker($restAuth, 'https://api.neverbounce.com');
        return new self($client);
    }

    public function validate(string $email): EmailValidationDTO
    {
        $response = $this->api->post('/v4/single/check', [
            'email' => $email,
        ]);

        return new EmailValidationDTO((array) $response);
    }

    public function bulkVerify(array $emails): int
    {
        $response = $this->api->post(
            '/v4/jobs/create',
            new BulkRequestDTO([
                'input_location' => 'supplied',
                'filename'       => "bulk-" . time() . ".csv",
                'auto_start'     => true,
                'auto_parse'     => true,
                'input'          => array_map(fn($e) => [$e], $emails),
            ])
        );

        if ($response->status !== 'success') {
            throw new NeverBounceAPIException(
                $response, 
                'Bulk validation failed.'
            );
        }

        return $response->job_id;
    }
}
```

**Look at how clean this is:**

1. **Authentication** is handled once in `build()`
2. **API calls** are simple one-liners
3. **Responses** are automatically typed DTOs
4. **Error handling** is straightforward
5. **The entire client** fits in ~150 lines (compared to 500+ with vanilla Guzzle)

### Usage Example

```php
$client = NeverBounceClient::build();

// Validate a single email
$result = $client->validate('test@example.com');

if ($result->result === EmailValidationDTO::RESULT_VALID) {
    echo "Email is valid!";
}

// Bulk validate
$jobId = $client->bulkVerify([
    'user1@example.com',
    'user2@example.com',
    'user3@example.com'
]);
```

Notice:
- **Type-safe constants** (`RESULT_VALID`) instead of magic strings
- **Clear return types** (`EmailValidationDTO`, `int`)
- **Zero boilerplate** - just business logic

---

## Part 4: Real-World Benefits

### 1. Catch API Contract Changes Early

When an API changes its response structure, SimpleDTO tells you immediately:

```php
try {
    $response = new BulkValidationDTO((array) $response);
} catch (InvalidDataTypeException $e) {
    throw new APIException(
        'The API contract has changed: ' . $e->getReasons()
    );
}
```

Instead of mysterious `undefined index` errors in production, you get **clear, actionable error messages**.

### 2. Self-Documenting Code

Constants and PHPDoc make the code self-explanatory:

```php
/**
 * @property-read string $status
 * @property-read string $result      // 'valid', 'invalid', 'disposable', etc.
 * @property-read string[] $flags
 * @property-read string $suggested_correction
 * @property-read int $execution_time
 */
final class EmailValidationDTO extends SimpleDTO
{
    public const RESULT_VALID = 'valid';
    public const RESULT_INVALID = 'invalid';
    // ...
}
```

Your IDE shows you:
- What fields exist
- What types they are
- What values are valid

### 3. Easy Testing

Because responses are DTOs, mocking is trivial:

```php
// In your tests
$mockDTO = new EmailValidationDTO([
    'status' => 'success',
    'result' => 'valid',
    'flags' => [],
    'suggested_correction' => '',
    'execution_time' => 123
]);
```

No need to mock HTTP responses or deal with JSON strings.

### 4. Refactoring Confidence

Change a DTO property? Your IDE will **find every usage** across your codebase. With arrays, you'd have to grep and hope.

---

## Comparison: Before and After

### Building an API Client for a Weather Service

**Without RESTSpeaker/SimpleDTO (Guzzle + Arrays):**

```php
class WeatherClient
{
    private Client $client;
    private string $apiKey;
    private ?string $token = null;
    
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client(['base_uri' => 'https://api.weather.com']);
        $this->authenticate();
    }
    
    private function authenticate(): void
    {
        $response = $this->client->post('/auth/token', [
            'form_params' => ['api_key' => $this->apiKey]
        ]);
        $data = json_decode($response->getBody(), true);
        $this->token = $data['access_token'];
    }
    
    public function getCurrentWeather(string $city): array
    {
        $response = $this->client->get("/weather/current/{$city}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->token]
        ]);
        
        $data = json_decode($response->getBody(), true);
        
        // Manual validation
        if (!isset($data['temperature'], $data['conditions'], $data['humidity'])) {
            throw new \Exception('Invalid response from API');
        }
        
        return $data;
    }
    
    public function getForecast(string $city, int $days): array
    {
        $response = $this->client->get("/weather/forecast/{$city}", [
            'headers' => ['Authorization' => 'Bearer ' . $this->token],
            'query' => ['days' => $days]
        ]);
        
        $data = json_decode($response->getBody(), true);
        
        if (!isset($data['forecast']) || !is_array($data['forecast'])) {
            throw new \Exception('Invalid forecast response');
        }
        
        return $data['forecast'];
    }
}

// Usage
$client = new WeatherClient('my-api-key');
$weather = $client->getCurrentWeather('London');
echo $weather['temperature']; // No autocomplete, no type safety
```

**~60 lines, no type safety, manual validation everywhere.**

---

**With RESTSpeaker/SimpleDTO:**

```php
/**
 * @property-read float $temperature
 * @property-read string $conditions
 * @property-read int $humidity
 */
class CurrentWeatherDTO extends SimpleDTO {}

/**
 * @property-read string $date
 * @property-read float $high
 * @property-read float $low
 * @property-read string $conditions
 */
class ForecastDayDTO extends SimpleDTO {}

class WeatherAuth extends RESTAuth
{
    protected function generateCustomAuthOptions(): array
    {
        return ['headers' => ['X-API-Key' => $this->token]];
    }
}

class WeatherClient
{
    private RESTSpeaker $api;
    
    public function __construct(string $apiKey)
    {
        $auth = new WeatherAuth($apiKey);
        $this->api = new RESTSpeaker($auth, 'https://api.weather.com');
    }
    
    public function getCurrentWeather(string $city): CurrentWeatherDTO
    {
        $response = $this->api->get("/weather/current/{$city}");
        return new CurrentWeatherDTO((array) $response);
    }
    
    public function getForecast(string $city, int $days): array
    {
        $response = $this->api->get("/weather/forecast/{$city}", [
            'query' => ['days' => $days]
        ]);
        
        return array_map(
            fn($day) => new ForecastDayDTO((array) $day),
            $response->forecast
        );
    }
}

// Usage
$client = new WeatherClient('my-api-key');
$weather = $client->getCurrentWeather('London');
echo $weather->temperature; // Full IDE autocomplete!
```

**~40 lines, fully typed, automatic validation, immutable DTOs.**

---

## When to Use This Stack

### Perfect For:

✅ **Building API client libraries/SDKs**  
✅ **Microservices communication**  
✅ **Third-party API integrations**  
✅ **Projects with complex data structures**  
✅ **Teams that value type safety**  
✅ **PHP 7.4+ projects** (required for typed properties)

### Maybe Overkill For:

❌ One-off API calls in a script  
❌ Projects still on PHP 7.3 or older  
❌ Simple webhooks with 1-2 fields  

---

## Getting Started

### Installation

```bash
composer require phpexperts/rest-speaker
composer require phpexperts/simple-dto
```

### Basic Example

```php
use PHPExperts\RESTSpeaker\RESTSpeaker;
use PHPExperts\RESTSpeaker\NoAuth;
use PHPExperts\SimpleDTO\SimpleDTO;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 */
class UserDTO extends SimpleDTO {}

// Create client
$api = new RESTSpeaker(new NoAuth(), 'https://jsonplaceholder.typicode.com');

// Make request
$response = $api->get('/users/1');

// Convert to DTO
$user = new UserDTO((array) $response);

// Use with full type safety
echo $user->name;  // Leanne Graham
echo $user->email; // Sincere@april.biz
```

### With Authentication

```php
use PHPExperts\RESTSpeaker\RESTAuth;

class BearerAuth extends RESTAuth
{
    protected function generateCustomAuthOptions(): array
    {
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token
            ]
        ];
    }
}

$api = new RESTSpeaker(
    new BearerAuth('your-token-here'),
    'https://api.example.com'
);
```

---

## Key Takeaways

1. **RESTSpeaker eliminates authentication boilerplate** - OAuth2, API keys, custom auth strategies all work out of the box

2. **SimpleDTO provides type safety without verbosity** - 7 lines instead of 20+ for each response object

3. **Together, they reduce SDK development time by 60-70%** while producing more maintainable code

4. **The ecosystem is proven in production** - 700,000+ SimpleDTO installs, real-world SDKs like NeverBounce

5. **Modern PHP features throughout** - Typed properties, strict types, nullable types, immutability

## Try It Yourself

The next time you need to build a PHP API client, skip the boilerplate. Use RESTSpeaker + SimpleDTO and focus on what actually matters: **your business logic**.

**Resources:**
- RESTSpeaker: https://github.com/phpexpertsinc/RESTSpeaker
- SimpleDTO: https://github.com/phpexpertsinc/SimpleDTO
- Example SDK: https://github.com/phpexpertsinc/NeverBounce

