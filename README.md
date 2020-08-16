# CORS Filter for CodeIgniter 4

Inspired from https://github.com/asm89/stack-cors for CodeIgniter 4

[![Latest Stable Version](https://poser.pugx.org/agungsugiarto/codeigniter4-cors/v)](https://packagist.org/packages/agungsugiarto/codeigniter4-cors)
[![Total Downloads](https://poser.pugx.org/agungsugiarto/codeigniter4-cors/downloads)](https://packagist.org/packages/agungsugiarto/codeigniter4-cors)
[![Latest Unstable Version](https://poser.pugx.org/agungsugiarto/codeigniter4-cors/v/unstable)](https://packagist.org/packages/agungsugiarto/codeigniter4-cors)
[![License](https://poser.pugx.org/agungsugiarto/codeigniter4-cors/license)](https://packagist.org/packages/agungsugiarto/codeigniter4-cors)

## **About**

The `codeigniter4-cors` package allows you to send [Cross-Origin Resource Sharing](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS/)
headers with Codeigniter4 filter configuration.

## **Features**

* Handles CORS pre-flight OPTIONS requests
* Adds CORS headers to your responses
* Match routes to only add CORS to certain Requests

## **Installation**

Require the `agungsugiarto/codeigniter4-cors` package in your `composer.json` and update your dependencies:
```sh
composer require agungsugiarto/codeigniter4-cors
```

## **Global usage**

To allow CORS for all your routes, first register `CorsFilter.php` filter at the top of the `$aliases` property of  `App/Config/Filter.php` class:

```php
public $aliases = [
    'cors' => \Fluent\Cors\Filters\CorsFilter::class,
    // ...
];
```

### **Global restrictions**
Restrict routes based on their URI pattern by editing **app/Config/Filters.php** and adding them to the
`$filters` array, e.g.:

```php
public filters = [
    // ...
    'cors' => ['after' => ['api/*']],
];
```

### **Restricting a single route**
Any single route can be restricted by adding the filter option to the last parameter in any of the route definition methods:
```php
$routes->match(['get', 'options'], 'api/users', 'UserController::index', ['filter' => 'cors'])
```

### **Restricting Route Groups**
In the same way, entire groups of routes can be restricted within the `group()` method:
```php
$routes->group('sample', ['filter' => 'cors'], function($routes) {
    // ...
});
```

## **Configuration**

The defaults are set in `config/cors.php`. Publish the config to copy the file to your own config:
```sh
php spark cors:publish
```
> **Note:** When using custom headers, like `X-Auth-Token` or `X-Requested-With`, you must set the `allowedHeaders` to include those headers. You can also set it to `['*']` to allow all custom headers.

> **Note:** If you are explicitly whitelisting headers, you must include `Origin` or requests will fail to be recognized as CORS.


### **Options**

| Option                   | Description                                                              | Default value |
|--------------------------|--------------------------------------------------------------------------|---------------|
| allowedOrigins           | Matches the request origin. Wildcards can be used, eg. `*.mydomain.com`  |    `['*']`    |
| allowedMethods           | Matches the request method.                                              |    `['*']`    |
| allowedHeaders           | Sets the Access-Control-Allow-Headers response header.                   |    `['*']`    |
| exposedHeaders           | Sets the Access-Control-Expose-Headers response header.                  |    `false`    |
| maxAge                   | Sets the Access-Control-Max-Age response header.                         |    `0`        |
| supportsCredentials      | Sets the Access-Control-Allow-Credentials header.                        |    `false`    |


`allowedOrigins`, `allowedHeaders` and `allowedMethods` can be set to `['*']` to accept any value.

> **Note:** For `allowedOrigins` you must include the scheme when not using a wildcard, eg. `['http://example.com', 'https://example.com']`.

> **Note:** Try to be a specific as possible. You can start developing with loose constraints, but it's better to be as strict as possible!

## **License**

Released under the MIT License, see [LICENSE](https://github.com/agungsugiarto/codeigniter4-cors/blob/master/LICENSE.md).
