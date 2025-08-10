# LaqueResponses

A framework-agnostic PHP package that generates HTTP responses cleanly and consistently across ecosystems.

## About

LaqueResponses provides a single, composable API to build HTTP responses (success, error, paginated, streamed, file, and problem+json) that plug into any framework via PSR-7/PSR-17 (messages/factories), PSR-15 (middleware, optional), and PSR-11 (container, optional).

## Features

- Framework-agnostic: works with Laravel, Symfony, Slim, Mezzio, Spiral, or bespoke stacks
- Consistent response envelopes across all your services
- Content negotiation based on Accept headers
- RFC 9457 Problem Details for HTTP APIs
- File and stream responses with proper headers
- Middleware for content negotiation and exception handling

## Requirements

- PHP 8.1+
- PSR-7/PSR-17 implementation (nyholm/psr7, laminas/laminas-diactoros, etc.)

## Installation

```bash
composer require vicent/laque-responses
```

## Basic Usage

```php
// 1. Set up the builder with a PSR-7/PSR-17 implementation
$factory = new \Nyholm\Psr7\Factory\Psr17Factory();
$registry = new \LaqueResponses\Registry\FormatterRegistry();
$registry->register(new \LaqueResponses\Formatters\JsonFormatter());
$registry->register(new \LaqueResponses\Formatters\TextFormatter());

$builder = new \LaqueResponses\Builder\ResponseBuilder(
    $factory, $factory, $registry
);

// 2. Create a response
$response = $builder->success([
    'user' => [
        'id' => 123,
        'name' => 'John Doe'
    ]
]);

// 3. Output the response (framework-specific)
// Laravel: return \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory::createResponse($response);
// Symfony: return $response;
// PHP-FPM/raw PHP:
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}", false);
    }
}
http_response_code($response->getStatusCode());
echo $response->getBody();
```

## Response Types

### Success Response

```php
$response = $builder->success(['user' => $user]);
```

Response:
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe"
    }
  }
}
```

### Error Response

```php
$response = $builder->error(
    'Validation failed', 
    422, 
    ['email' => ['Email is required']]
);
```

Response:
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["Email is required"]
  }
}
```

### Paginated Response

```php
$response = $builder->paginated(
    $items,     // Array of items for current page
    $total,     // Total number of items
    $page,      // Current page number
    $perPage    // Items per page
);
```

Response:
```json
{
  "status": "success",
  "meta": {
    "total": 100,
    "page": 2,
    "per_page": 15,
    "pages": 7
  },
  "data": [
    { "id": 16, "name": "Item 16" },
    ...
  ]
}
```

### Created Response

```php
$response = $builder->created(
    '/users/123',    // Location header value
    $createdUser     // Data to include in response
);
```

Response (with 201 status code and Location header):
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "name": "John Doe"
  }
}
```

### No Content Response

```php
$response = $builder->noContent();
```

Returns a 204 No Content response.

### Problem Details Response (RFC 9457)

```php
$response = $builder->problem(
    'https://example.com/problems/out-of-stock',
    'Item Out of Stock',
    400,
    'Item #12345 is currently out of stock',
    '/orders/12345',
    ['available_at' => '2025-09-15T12:00:00Z']
);
```

Response (with application/problem+json content type):
```json
{
  "type": "https://example.com/problems/out-of-stock",
  "title": "Item Out of Stock",
  "status": 400,
  "detail": "Item #12345 is currently out of stock",
  "instance": "/orders/12345",
  "available_at": "2025-09-15T12:00:00Z"
}
```

### File Download

```php
$streamBuilder = new \LaqueResponses\Builder\StreamResponseBuilder($factory, $factory);

$response = $streamBuilder->file(
    '/path/to/report.pdf',
    'quarterly-report-2025.pdf',
    'application/pdf'
);
```

Returns a response with appropriate headers for file download.

### Streaming Response

```php
$response = $streamBuilder->stream(
    function ($stream) {
        $stream->write('Line 1' . PHP_EOL);
        $stream->write('Line 2' . PHP_EOL);
        $stream->write('Line 3' . PHP_EOL);
    },
    200,
    'text/plain'
);
```

## Framework Integration

### Laravel

```php
// In a service provider

use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Formatters\JsonFormatter;
use LaqueResponses\Formatters\TextFormatter;
use LaqueResponses\Registry\FormatterRegistry;
use Nyholm\Psr7\Factory\Psr17Factory;

public function register(): void
{
    $this->app->singleton(FormatterRegistry::class, function() {
        $registry = new FormatterRegistry();
        $registry->register(new JsonFormatter(prettyPrint: $this->app->isDebug()));
        $registry->register(new TextFormatter());
        return $registry;
    });
    
    $this->app->singleton(ResponseBuilder::class, function($app) {
        $factory = new Psr17Factory();
        return new ResponseBuilder(
            $factory,
            $factory,
            $app->make(FormatterRegistry::class)
        );
    });
}

// In a controller

use LaqueResponses\Builder\ResponseBuilder;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class UserController extends Controller
{
    public function show(ResponseBuilder $builder, $id)
    {
        $user = User::findOrFail($id);
        $response = $builder->success($user);
        
        return HttpFoundationFactory::createResponse($response);
    }
}
```

### Slim

```php
// In your dependencies container

use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Error\DefaultExceptionMapper;
use LaqueResponses\Error\ProblemDetailsFactory;
use LaqueResponses\Formatters\JsonFormatter;
use LaqueResponses\Registry\FormatterRegistry;
use Psr\Container\ContainerInterface;

return [
    FormatterRegistry::class => function() {
        $registry = new FormatterRegistry();
        $registry->register(new JsonFormatter());
        return $registry;
    },
    
    ResponseBuilder::class => function(ContainerInterface $c) {
        return new ResponseBuilder(
            $c->get('responseFactory'),
            $c->get('streamFactory'),
            $c->get(FormatterRegistry::class)
        );
    },
    
    ProblemDetailsFactory::class => function(ContainerInterface $c) {
        return new ProblemDetailsFactory(
            $c->get(ResponseBuilder::class),
            new DefaultExceptionMapper(),
            $c->get('settings')['debug'] ?? false
        );
    }
];

// In your route handler

$app->get('/users/{id}', function($request, $response, $args) {
    $builder = $this->get(ResponseBuilder::class);
    
    try {
        $user = $this->get(UserRepository::class)->findById($args['id']);
        return $builder->success($user);
    } catch (NotFoundException $e) {
        return $builder->error('User not found', 404);
    }
});
```

## Custom Formatters

You can create your own formatters by implementing the `ResponseFormatterInterface`:

```php
use LaqueResponses\Contracts\ResponseFormatterInterface;

final class XmlFormatter implements ResponseFormatterInterface
{
    public function contentType(): string
    {
        return 'application/xml';
    }

    public function format(array|object|string|int|float|bool|null $payload): string
    {
        // Implementation to convert the payload to XML string
        // ...
        
        return $xml;
    }
}

// Register your formatter
$registry->register(new XmlFormatter());
```

## Error Mapping

You can customize how exceptions are mapped to problem responses:

```php
use LaqueResponses\Error\ExceptionMapperInterface;

class AppExceptionMapper implements ExceptionMapperInterface
{
    public function map(\Throwable $e, bool $debug = false): array
    {
        // Map specific exceptions to problem details
        return match (true) {
            $e instanceof RateLimitException => [
                'type' => 'https://problem/rate-limit',
                'title' => 'Too Many Requests',
                'status' => 429,
                'detail' => $e->getMessage(),
                'extensions' => [
                    'retry_after' => $e->getRetryAfter(),
                ]
            ],
            // Default mapping
            default => [
                'type' => 'about:blank',
                'title' => 'Internal Server Error',
                'status' => 500,
                'detail' => $debug ? $e->getMessage() : 'An unexpected error occurred',
                'extensions' => [],
            ],
        };
    }
}

// Use your custom mapper
$problemFactory = new ProblemDetailsFactory(
    $builder,
    new AppExceptionMapper(),
    $debug
);
```

## Configuration

```php
use LaqueResponses\Config;

// Create from array
$config = Config::fromArray([
    'default_content_type' => 'application/json',
    'dev_mode' => true,
    'cache_control_default' => 'no-store',
    'negotiation' => [
        'strict_406' => false,
    ],
    'problem' => [
        'include_trace_id' => true,
        'trace_header' => 'X-Trace-Id',
        'default_type' => 'about:blank',
    ],
    'pagination' => [
        'max_per_page' => 100,
        'default_per_page' => 20,
    ],
]);

// Or directly
$config = new Config(
    defaultContentType: 'application/json',
    devMode: true,
    defaultCacheControl: 'no-store',
    strict406: false
);

// Create a builder with config
$builder = new ResponseBuilder(
    $responseFactory,
    $streamFactory,
    $registry,
    $negotiator,
    $config->defaultContentType,
    $config->defaultCacheControl
);
```

## License

MIT License. See LICENSE file for details.