<?php

declare(strict_types=1);

namespace LaqueResponses\Registry;

use LaqueResponses\Contracts\ResponseFormatterInterface;

/**
 * Registry for response formatters
 */
final class FormatterRegistry
{
    /**
     * @var array<string, ResponseFormatterInterface> Formatters indexed by content type
     */
    private array $formatters = [];

    /**
     * Register a formatter
     */
    public function register(ResponseFormatterInterface $formatter): void
    {
        $this->formatters[$formatter->contentType()] = $formatter;
    }

    /**
     * Get a formatter for a specific content type
     */
    public function get(string $contentType): ?ResponseFormatterInterface
    {
        // Support content type with parameters like "application/json; charset=utf-8"
        $baseType = explode(';', $contentType)[0];
        
        return $this->formatters[$baseType] ?? null;
    }

    /**
     * Get list of supported content types
     * 
     * @return list<string>
     */
    public function supported(): array
    {
        return array_keys($this->formatters);
    }
}