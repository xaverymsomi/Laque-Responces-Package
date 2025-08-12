<?php

declare(strict_types=1);

namespace LaqueResponses\Formatters;

use LaqueResponses\Contracts\ResponseFormatterInterface;
use LaqueResponses\Support\ArrayUtils;
use RuntimeException;

/**
 * Formats data as CSV
 */
final class CsvFormatter implements ResponseFormatterInterface
{
    /**
     * @param string $delimiter CSV field delimiter
     * @param string $enclosure CSV field enclosure
     * @param string $escapeChar CSV escape character
     * @param bool $includeHeaders Whether to include header row
     */
    public function __construct(
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $escapeChar = '\\',
        private bool $includeHeaders = true
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function contentType(): string
    {
        return 'text/csv';
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException If data cannot be formatted as CSV
     */
    public function format(array|object|string|int|float|bool|null $payload): string
    {
        // Only arrays can be converted to CSV
        if (! is_array($payload)) {
            if (is_object($payload)) {
                $payload = ArrayUtils::objectToArray($payload);
            } else {
                throw new RuntimeException('CSV formatter requires array data');
            }
        }

        // Handle empty array
        if (empty($payload)) {
            return '';
        }

        // Create a memory stream for CSV
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Failed to open temporary stream for CSV formatting');
        }

        try {
            // If not already a list of rows, wrap in array
            $rows = ArrayUtils::isAssociative($payload) ? [$payload] : $payload;

            // Extract headers from first row
            $headers = array_keys($rows[0] ?? []);

            // Write headers if requested
            if ($this->includeHeaders && ! empty($headers)) {
                fputcsv($stream, $headers, $this->delimiter, $this->enclosure, $this->escapeChar);
            }

            // Write data rows
            foreach ($rows as $row) {
                // Normalize to array if object
                if (is_object($row)) {
                    $row = ArrayUtils::objectToArray($row);
                }

                // Skip non-array rows
                if (! is_array($row)) {
                    continue;
                }

                // Ensure all rows have the same structure
                if ($this->includeHeaders) {
                    $data = [];
                    foreach ($headers as $header) {
                        $data[] = $row[$header] ?? '';
                    }
                    fputcsv($stream, $data, $this->delimiter, $this->enclosure, $this->escapeChar);
                } else {
                    fputcsv($stream, $row, $this->delimiter, $this->enclosure, $this->escapeChar);
                }
            }

            // Get content as string
            rewind($stream);
            $content = stream_get_contents($stream);

            if ($content === false) {
                throw new RuntimeException('Failed to read CSV content from stream');
            }

            return $content;
        } finally {
            fclose($stream);
        }
    }
}
