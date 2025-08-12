<?php

declare(strict_types=1);

namespace LaqueResponses\Formatters;

use LaqueResponses\Contracts\ResponseFormatterInterface;
use LaqueResponses\Support\ArrayUtils;
use RuntimeException;

/**
 * Formats data as Newline-Delimited JSON (NDJSON)
 */
final class NdJsonFormatter implements ResponseFormatterInterface
{
    /**
     * @param int $jsonFlags Additional JSON encoding flags
     */
    public function __construct(
        private int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function contentType(): string
    {
        return 'application/x-ndjson';
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException If JSON encoding fails
     */
    public function format(array|object|string|int|float|bool|null $payload): string
    {
        // If not array, wrap in array
        if (! is_array($payload)) {
            $payload = [$payload];
        }

        // If associative array, wrap in array
        if (ArrayUtils::isAssociative($payload)) {
            $payload = [$payload];
        }

        $lines = [];

        foreach ($payload as $item) {
            $json = json_encode($item, $this->jsonFlags);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg());
            }

            $lines[] = $json;
        }

        return implode(PHP_EOL, $lines);
    }
}
