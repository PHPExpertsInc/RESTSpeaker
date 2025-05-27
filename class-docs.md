# **phpexperts/rest-speaker Documentation**  
*A Guzzle-based REST client with JSON automation and authentication support.*  

---

## **1. Public API Reference**  

### **RESTSpeaker Class**  
**Primary HTTP methods:**  

#### **`__construct(RESTAuth $auth, string $baseURI)`**  
Initializes the API client with authentication and base URI.  
*Example:*  
```php
$auth = new RESTAuth(RESTAuth::AUTH_MODE_XAPI);
$api = new RESTSpeaker($auth, 'https://api.example.com/');
```  
*Explanation:*  
Sets up the REST client with a given authentication strategy and base URL. Ensures trailing slashes and proper headers.  

---

#### **`get(string $uri, array $options = []): mixed`**  
Sends a GET request and returns decoded JSON or raw content.  
*Example:*  
```php
$data = $api->get('v1/users/123');
```  
*Explanation:*  
Automatically decodes JSON responses if `Content-Type: application/json` is detected, otherwise returns raw response.  

---

#### **`post(string $uri, array|object $data = [], array $options = []): mixed`**  
Sends a POST request with automatic JSON encoding.  
*Example:*  
```php
$result = $api->post('v1/users', ['name' => 'John Doe']);
```  
*Explanation:*  
Converts `$data` to JSON and sets `Content-Type: application/json`.  

---

#### **`put(string $uri, array|object $data = [], array $options = []): mixed`**  
Sends a PUT request with automatic JSON encoding.  
*Example:*  
```php
$api->put('v1/users/123', ['name' => 'Jane Doe']);
```  

---

#### **`patch(string $uri, array|object $data = [], array $options = []): mixed`**  
Sends a PATCH request with automatic JSON encoding.  
*Example:*  
```php
$api->patch('v1/users/123', ['email' => 'jane@example.com']);
```  

---

#### **`delete(string $uri, array $options = []): mixed`**  
Sends a DELETE request.  
*Example:*  
```php
$api->delete('v1/users/123');
```  

---

#### **`getLastRawResponse(): ?ResponseInterface`**  
Returns the last Guzzle response object.  
*Example:*  
```php
$rawResponse = $api->getLastRawResponse();
```  

---

#### **`getLastStatusCode(): ?int`**  
Returns the last HTTP status code.  
*Example:*  
```php
$status = $api->getLastStatusCode();
```  

---

### **HTTPSpeaker (Low-Level Client)**  
#### **`getLastRawResponse(): ?ResponseInterface`**  
Returns the last raw Guzzle response.  
*Example:*  
```php
$raw = $api->http->getLastRawResponse();
```  

#### **`getLastStatusCode(): ?int`**  
Returns the last HTTP status code.  
*Example:*  
```php
$status = $api->http->getLastStatusCode();
```  

---

### **RESTAuth (Authentication Handler)**  
#### **`generateGuzzleAuthOptions(): array`**  
Generates authentication headers/options for Guzzle.  
*Example:*  
```php
$authHeaders = $auth->generateGuzzleAuthOptions();
```  

---

## **2. Feature Use Case: OpenWeatherMap Geocoding API**  

### **Step 1: Install Dependencies**  
```bash
composer require phpexperts/rest-speaker phpexperts/simple-dto
```  

### **Step 2: Define a Protected DTO**  
```php
use PHPExperts\SimpleDTO\SimpleDTO;
use PHPExperts\SimpleDTO\WriteOnce;

class GeoLocationDTO extends SimpleDTO
{
    use WriteOnce;

    protected string $name;
    protected float $lat;
    protected float $lon;
    protected string $country;
}
```  

### **Step 3: Fetch and Map API Data**  
```php
use PHPExperts\RESTSpeaker\RESTAuth;
use PHPExperts\RESTSpeaker\RESTSpeaker;

// Configure API client
$auth = new RESTAuth(RESTAuth::AUTH_MODE_XAPI);
$api = new RESTSpeaker($auth, 'http://api.openweathermap.org/geo/1.0/');

// Fetch geocoding data
$response = $api->get('direct', [
    'query' => [
        'q'     => 'London,GB',
        'appid' => env('OPENWEATHER_API_KEY'),  // Uses RESTSpeaker's env() helper
    ],
]);

// Map response to DTOs
$locations = array_map(
    fn($locData) => new GeoLocationDTO($locData),
    $response
);

// Access data via getters
echo $locations[0]->getName();  // "London"
echo $locations[0]->getLat();   // 51.5074
```  

---

## **3. Key Features**  
✅ **Automatic JSON Handling** – Decodes responses and encodes requests automatically.  
✅ **PSR-18 Compliance** – Works with Guzzle middleware and PSR standards.  
✅ **Protected DTOs** – Secure data encapsulation with `SimpleDTO`.  
✅ **Environment-Agnostic** – Uses `env()` for config (works with `.env` files or server variables).  

--- 

## **4. License**  
MIT (see [LICENSE](https://github.com/phpexpertsinc/RESTSpeaker/blob/master/LICENSE)).
