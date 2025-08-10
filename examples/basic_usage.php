<?php

// This is an example of basic usage of the LaqueResponses package
// In a real application, you would use Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Import necessary classes
use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Builder\StreamResponseBuilder;
use LaqueResponses\Error\DefaultExceptionMapper;
use LaqueResponses\Error\ProblemDetailsFactory;
use LaqueResponses\Formatters\JsonFormatter;
use LaqueResponses\Formatters\TextFormatter;
use LaqueResponses\Registry\FormatterRegistry;
use LaqueResponses\Support\Status;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * Helper function to output a PSR-7 Response
 */
function outputResponse($response): void
{
    // Set HTTP status code
    http_response_code($response->getStatusCode());
    
    // Set headers
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
    
    // Output body
    echo $response->getBody();
}

// Create factories and registry
$factory = new Psr17Factory();
$registry = new FormatterRegistry();

// Register formatters
$registry->register(new JsonFormatter(prettyPrint: true)); // Pretty print for example
$registry->register(new TextFormatter());

// Create response builder
$builder = new ResponseBuilder(
    $factory,
    $factory,
    $registry
);

// Create a problem details factory for error handling
$problemFactory = new ProblemDetailsFactory(
    $builder,
    new DefaultExceptionMapper(),
    true // Debug mode for example
);

// Create a stream response builder for file downloads
$streamBuilder = new StreamResponseBuilder($factory, $factory);

// Example usage based on a simple "route" parameter
$action = $_GET['action'] ?? 'success';

try {
    switch ($action) {
        case 'success':
            $data = [
                'user' => [
                    'id' => 123,
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'roles' => ['user', 'admin']
            ];
            $response = $builder->success($data);
            break;
            
        case 'error':
            $response = $builder->error(
                'Validation failed',
                Status::UNPROCESSABLE_ENTITY,
                [
                    'email' => ['Email is required'],
                    'password' => ['Password must be at least 8 characters']
                ]
            );
            break;
            
        case 'paginated':
            $items = [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
                ['id' => 3, 'name' => 'Item 3'],
                ['id' => 4, 'name' => 'Item 4'],
                ['id' => 5, 'name' => 'Item 5'],
            ];
            $response = $builder->paginated($items, 25, 1, 5);
            break;
            
        case 'created':
            $newUser = [
                'id' => 456,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com'
            ];
            $response = $builder->created('/users/456', $newUser);
            break;
            
        case 'no-content':
            $response = $builder->noContent();
            break;
            
        case 'problem':
            $response = $builder->problem(
                'https://example.com/problems/out-of-stock',
                'Item Out of Stock',
                Status::BAD_REQUEST,
                'The requested item is currently unavailable',
                '/products/123',
                ['available_date' => '2025-09-01']
            );
            break;
            
        case 'exception':
            // Example of handling an exception
            try {
                throw new RuntimeException('Something went wrong');
            } catch (Throwable $e) {
                $response = $problemFactory->from($e, '/api/example');
            }
            break;
            
        case 'stream':
            // Create a simple text stream response
            $response = $streamBuilder->stream(
                function ($stream) {
                    $stream->write("Line 1\n");
                    $stream->write("Line 2\n");
                    $stream->write("Line 3\n");
                },
                Status::OK,
                'text/plain'
            );
            break;
            
        default:
            $response = $builder->error('Invalid action', Status::NOT_FOUND);
            break;
    }
    
    // Output the response
    outputResponse($response);
} catch (Throwable $e) {
    // Fallback error handling
    $response = $problemFactory->from($e);
    outputResponse($response);
}