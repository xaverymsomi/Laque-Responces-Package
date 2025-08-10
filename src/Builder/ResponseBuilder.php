<?php

declare(strict_types=1);

namespace LaqueResponses\Builder;

use LaqueResponses\Contracts\ResponseFormatterInterface;
use LaqueResponses\Negotiation\AcceptHeaderNegotiator;
use LaqueResponses\Registry\FormatterRegistry;
use LaqueResponses\Support\ContentType;
use LaqueResponses\Support\Headers;
use LaqueResponses\Support\Status;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Main builder for HTTP responses
 */
final class ResponseBuilder
{
    /**
     * Default cache control for responses
     */
    private string $defaultCacheControl;

    /**
     * @param ResponseFactoryInterface $responseFactory PSR-17 response factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     * @param FormatterRegistry $registry Registry of available formatters
     * @param AcceptHeaderNegotiator|null $negotiator Negotiator for content type selection
     * @param string $defaultContentType Default content type if not specified
     * @param string $defaultCacheControl Default Cache-Control header value
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private FormatterRegistry $registry,
        private ?AcceptHeaderNegotiator $negotiator = null,
        private string $defaultContentType = ContentType::JSON,
        string $defaultCacheControl = 'no-store'
    ) {
        $this->defaultCacheControl = $defaultCacheControl;
    }

    /**
     * Create a response with the given payload, status, and content type
     *
     * @param array<string,mixed>|object|string|int|float|bool|null $payload Response payload
     * @param int $status HTTP status code
     * @param string|null $contentType Content type (if null, uses Accept header or default)
     * @param array<string,string|array<string>> $headers Additional headers
     * @return ResponseInterface The PSR-7 response
     * @throws RuntimeException If no suitable formatter is found
     */
    public function make(
        array|object|string|int|float|bool|null $payload,
        int $status = Status::OK,
        ?string $contentType = null,
        array $headers = []
    ): ResponseInterface {
        // Determine content type
        $effectiveContentType = $contentType ?? $this->defaultContentType;
        
        // Get formatter for the content type
        $formatter = $this->registry->get($effectiveContentType);
        
        if ($formatter === null) {
            throw new RuntimeException("No formatter available for content type: {$effectiveContentType}");
        }
        
        // Format the payload
        $formattedBody = $formatter->format($payload);
        
        // Create response with status code
        $response = $this->responseFactory->createResponse($status);
        
        // Create body stream
        $body = $this->streamFactory->createStream($formattedBody);
        
        // Set body and Content-Type header
        $response = $response
            ->withBody($body)
            ->withHeader(Headers::CONTENT_TYPE, $formatter->contentType());
        
        // Set default cache control if not in headers
        if (!isset($headers[Headers::CACHE_CONTROL])) {
            $response = $response->withHeader(Headers::CACHE_CONTROL, $this->defaultCacheControl);
        }
        
        // Add all other headers
        foreach ($headers as $name => $value) {
            // Skip if we've already set this header
            if ($name === Headers::CONTENT_TYPE) {
                continue;
            }
            
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (Headers::isSafe($v)) {
                        $response = $response->withAddedHeader($name, $v);
                    }
                }
            } elseif (Headers::isSafe($value)) {
                $response = $response->withHeader($name, $value);
            }
        }
        
        return $response;
    }

    /**
     * Create a success response
     * 
     * @param mixed $data Response data
     * @param int $status HTTP status code
     * @param string|null $contentType Content type (if null, uses Accept header or default)
     * @return ResponseInterface The PSR-7 response
     */
    public function success(mixed $data = null, int $status = Status::OK, ?string $contentType = null): ResponseInterface
    {
        $payload = [
            'status' => 'success',
            'data' => $data
        ];
        
        return $this->make($payload, $status, $contentType);
    }

    /**
     * Create an error response
     * 
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array<string,mixed> $errors Additional error details
     * @param string|null $contentType Content type (if null, uses Accept header or default)
     * @return ResponseInterface The PSR-7 response
     */
    public function error(
        string $message,
        int $status = Status::BAD_REQUEST,
        array $errors = [],
        ?string $contentType = null
    ): ResponseInterface {
        $payload = [
            'status' => 'error',
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        
        return $this->make($payload, $status, $contentType);
    }

    /**
     * Create a paginated response
     * 
     * @param array<mixed> $items Items for the current page
     * @param int $total Total number of items
     * @param int $page Current page number (1-based)
     * @param int $perPage Number of items per page
     * @param string|null $contentType Content type (if null, uses Accept header or default)
     * @return ResponseInterface The PSR-7 response
     */
    public function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage,
        ?string $contentType = null
    ): ResponseInterface {
        // Ensure sensible values
        $total = max(0, $total);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        
        // Calculate number of pages
        $pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        
        $payload = [
            'status' => 'success',
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => $pages
            ],
            'data' => $items
        ];
        
        return $this->make($payload, Status::OK, $contentType);
    }

    /**
     * Create a 201 Created response with Location header
     * 
     * @param string|UriInterface $location URI of the created resource
     * @param mixed $data Response data
     * @param string|null $contentType Content type (if null, uses Accept header or default)
     * @return ResponseInterface The PSR-7 response
     */
    public function created(string|UriInterface $location, mixed $data = null, ?string $contentType = null): ResponseInterface
    {
        $payload = [
            'status' => 'success',
            'data' => $data
        ];
        
        $headers = [
            Headers::LOCATION => (string) $location
        ];
        
        return $this->make($payload, Status::CREATED, $contentType, $headers);
    }

    /**
     * Create a 204 No Content response
     * 
     * @return ResponseInterface The PSR-7 response
     */
    public function noContent(): ResponseInterface
    {
        return $this->responseFactory->createResponse(Status::NO_CONTENT)
            ->withHeader(Headers::CACHE_CONTROL, $this->defaultCacheControl);
    }

    /**
     * Create a problem details response per RFC 9457
     * 
     * @param string $type URI reference that identifies the problem type
     * @param string $title Short human-readable summary of the problem type
     * @param int $status HTTP status code
     * @param string|null $detail Human-readable explanation specific to this occurrence
     * @param string|null $instance URI reference that identifies the specific occurrence of the problem
     * @param array<string,mixed> $extensions Additional members for the problem detail
     * @return ResponseInterface The PSR-7 response
     */
    public function problem(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = []
    ): ResponseInterface {
        $problem = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
        ];
        
        if ($detail !== null) {
            $problem['detail'] = $detail;
        }
        
        if ($instance !== null) {
            $problem['instance'] = $instance;
        }
        
        // Add extensions as top-level properties
        foreach ($extensions as $key => $value) {
            $problem[$key] = $value;
        }
        
        return $this->make($problem, $status, ContentType::PROBLEM_JSON);
    }
}