<?php

declare(strict_types=1);

namespace LaqueResponses;

/**
 * Configuration for the LaqueResponses package
 */
final class Config
{
    /**
     * @param string $defaultContentType Default content type for responses
     * @param bool $devMode Whether the application is in development mode
     * @param string $defaultCacheControl Default Cache-Control header value
     * @param bool $strict406 Whether to return 406 Not Acceptable if no match
     * @param bool $includeTraceId Whether to include trace ID in problem responses
     * @param string $traceHeader Header name for trace ID
     * @param string $defaultProblemType Default type URI for problem responses
     * @param int $maxPerPage Maximum items per page for paginated responses
     * @param int $defaultPerPage Default items per page for paginated responses
     */
    public function __construct(
        public readonly string $defaultContentType = 'application/json',
        public readonly bool $devMode = false,
        public readonly string $defaultCacheControl = 'no-store',
        public readonly bool $strict406 = false,
        public readonly bool $includeTraceId = true,
        public readonly string $traceHeader = 'X-Trace-Id',
        public readonly string $defaultProblemType = 'about:blank',
        public readonly int $maxPerPage = 100,
        public readonly int $defaultPerPage = 20
    ) {
    }

    /**
     * Create a new Config from an array
     * 
     * @param array<string,mixed> $options
     * @return self
     */
    public static function fromArray(array $options): self
    {
        return new self(
            defaultContentType: $options['default_content_type'] ?? 'application/json',
            devMode: $options['dev_mode'] ?? false,
            defaultCacheControl: $options['cache_control_default'] ?? 'no-store',
            strict406: $options['negotiation']['strict_406'] ?? false,
            includeTraceId: $options['problem']['include_trace_id'] ?? true,
            traceHeader: $options['problem']['trace_header'] ?? 'X-Trace-Id',
            defaultProblemType: $options['problem']['default_type'] ?? 'about:blank',
            maxPerPage: $options['pagination']['max_per_page'] ?? 100,
            defaultPerPage: $options['pagination']['default_per_page'] ?? 20
        );
    }

    /**
     * Create a new instance with modified values
     * 
     * @param array<string,mixed> $options
     * @return self
     */
    public function with(array $options): self
    {
        return new self(
            defaultContentType: $options['defaultContentType'] ?? $this->defaultContentType,
            devMode: $options['devMode'] ?? $this->devMode,
            defaultCacheControl: $options['defaultCacheControl'] ?? $this->defaultCacheControl,
            strict406: $options['strict406'] ?? $this->strict406,
            includeTraceId: $options['includeTraceId'] ?? $this->includeTraceId,
            traceHeader: $options['traceHeader'] ?? $this->traceHeader,
            defaultProblemType: $options['defaultProblemType'] ?? $this->defaultProblemType,
            maxPerPage: $options['maxPerPage'] ?? $this->maxPerPage,
            defaultPerPage: $options['defaultPerPage'] ?? $this->defaultPerPage
        );
    }
}