<?php

declare(strict_types=1);

namespace LaqueResponses\Contracts;

/**
 * Interface for response formatters that convert data to formatted strings
 */
interface ResponseFormatterInterface
{
    /**
     * Returns the MIME content type this formatter handles
     * 
     * @return string The content type, e.g. 'application/json'
     */
    public function contentType(): string;

    /**
     * Format the given payload into a string representation
     * 
     * @param array<string,mixed>|object|string|int|float|bool|null $payload
     * @return string The formatted payload
     */
    public function format(array|object|string|int|float|bool|null $payload): string;
}