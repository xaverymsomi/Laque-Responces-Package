<?php

declare(strict_types=1);

namespace LaqueResponses\Formatters;

use LaqueResponses\Contracts\ResponseFormatterInterface;
use RuntimeException;

/**
 * Formats data as JSON
 */
final class JsonFormatter implements ResponseFormatterInterface
{
    /**
     * @param bool $prettyPrint Whether to pretty-print the JSON output
     * @param bool $checkNumeric Whether to validate numeric values
     * @param int $jsonFlags Additional JSON encoding flags
     */
    public function __construct(
        private bool $prettyPrint = false,
        private bool $checkNumeric = true,
        private int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function contentType(): string
    {
        return 'application/json';
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException If JSON encoding fails
     */
    public function format(array|object|string|int|float|bool|null $payload): string
    {
        $flags = $this->jsonFlags;
        
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }
        
        $json = json_encode($payload, $flags);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }
        
        if ($this->checkNumeric && is_string($json) && $json === 'NaN' || $json === 'INF' || $json === '-INF') {
            throw new RuntimeException('Invalid numeric value detected: ' . $json);
        }
        
        return $json;
    }
}