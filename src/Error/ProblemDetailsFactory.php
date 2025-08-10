<?php

declare(strict_types=1);

namespace LaqueResponses\Error;

use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Support\ContentType;
use Psr\Http\Message\ResponseInterface;

/**
 * Factory for creating Problem Details responses (RFC 9457)
 */
final class ProblemDetailsFactory
{
    /**
     * @param ResponseBuilder $builder Response builder to use
     * @param ExceptionMapperInterface $mapper Exception mapper to use
     * @param bool $debug Whether to include debug information
     * @param string $traceHeader Header name for trace ID
     */
    public function __construct(
        private ResponseBuilder $builder,
        private ExceptionMapperInterface $mapper,
        private bool $debug = false,
        private string $traceHeader = 'X-Trace-Id'
    ) {
    }

    /**
     * Create a problem details response from an exception
     * 
     * @param \Throwable $e The exception
     * @param string|null $instance URI reference that identifies the specific occurrence of the problem
     * @param array<string, mixed> $extra Additional problem details extensions
     * @return ResponseInterface
     */
    public function from(\Throwable $e, ?string $instance = null, array $extra = []): ResponseInterface
    {
        // Map the exception to problem details
        $details = $this->mapper->map($e, $this->debug);
        
        // Add instance if provided
        if ($instance !== null) {
            $problem['instance'] = $instance;
        }
        
        // Add any extra extensions
        $details['extensions'] = array_merge($details['extensions'], $extra);
        
        // Extract fields from the mapping
        $problem = [
            'type' => $details['type'],
            'title' => $details['title'],
            'status' => $details['status'],
        ];
        
        // Add detail if available
        if ($details['detail'] !== null) {
            $problem['detail'] = $details['detail'];
        }
        
        // Add instance if provided
        if ($instance !== null) {
            $problem['instance'] = $instance;
        }
        
        // Add extensions as top-level properties
        foreach ($details['extensions'] as $key => $value) {
            $problem[$key] = $value;
        }
        
        // Create the response
        $response = $this->builder->make(
            $problem,
            $details['status'],
            ContentType::PROBLEM_JSON
        );
        
        // Add trace ID header if available in extensions
        if (isset($details['extensions']['error_ref'])) {
            $response = $response->withHeader($this->traceHeader, $details['extensions']['error_ref']);
        }
        
        return $response;
    }
}