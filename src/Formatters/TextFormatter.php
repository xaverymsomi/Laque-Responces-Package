<?php

declare(strict_types=1);

namespace LaqueResponses\Formatters;

use LaqueResponses\Contracts\ResponseFormatterInterface;

/**
 * Formats data as plain text
 */
final class TextFormatter implements ResponseFormatterInterface
{
    /**
     * @param int $maxLength Maximum length for array/object string representation
     */
    public function __construct(
        private int $maxLength = 10000
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function contentType(): string
    {
        return 'text/plain; charset=utf-8';
    }

    /**
     * {@inheritDoc}
     */
    public function format(array|object|string|int|float|bool|null $payload): string
    {
        if ($payload === null) {
            return '';
        }
        
        if (is_scalar($payload)) {
            return (string) $payload;
        }
        
        $text = print_r($payload, true);
        
        if (strlen($text) > $this->maxLength) {
            $text = substr($text, 0, $this->maxLength) . '... [truncated]';
        }
        
        return $text;
    }
}