# RESTSpeaker

RESTSpeaker is a PHP Experts, Inc., Project meant to ease the accessing of APIs.

This library's Speaker classes utilize the Guzzle HTTP Client
via the Composition architectural pattern.

It further extends base Guzzle so that it automagically decodes
JSON responses and is much easier to work with.

## Installation

Via Composer

```bash
composer require phpexperts/rest-speaker
```

## Change log

Please see the [changelog](CHANGELOG.md) for more information on what has changed recently.

## Usage

```php
	// Instantiation:
	// NOTE: Guzzle *requires* baseURIs to end with "/".
	$baseURI = 'https://api.myservice.dev/';

	// Either use an .env file or configure ussing the appropriate setters.
	$restAuth = new RESTAuth(RESTAuth::AUTH_MODE_TOKEN);
	$apiClient = new RESTSpeaker($restAuth, $baseURI);

	$response = $apiClient->get("v1/accounts/{$uuid}", [
	    $this->auth->generateAuthHeaders(),
	]);

	print_r($response);

	/** Output:
	stdClass Object
	(
	    [the] => actual
	    [json] => stdClass Object
        (
            [object] => 1
            [returned] => stdClass Object
            (
                [as] => if
                [run-through] => json_decode()
            )
        )
	)
	 */

	// Get the more to-the-metal HTTPSpeaker:
	$apiClient->http->get('/someURI');
```
## ChangeLog

Please see the [changelog](CHANGELOG.md) for more information on what has changed recently.

## Testing

```bash
phpunit
```

# Contributors

[Theodore R. Smith](https://www.phpexperts.pro/]) <theodore@phpexperts.pro>  
GPG Fingerprint: 4BF8 2613 1C34 87AC D28F  2AD8 EB24 A91D D612 5690  
CEO: PHP Experts, Inc.

## License

MIT license. Please see the [license file](LICENSE) for more information.

