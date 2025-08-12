<?php

declare(strict_types=1);

namespace LaqueResponses\Support;

/**
 * Content type constants and utilities
 */
final class ContentType
{
    public const JSON = 'application/json';
    public const TEXT = 'text/plain';
    public const HTML = 'text/html';
    public const XML = 'application/xml';
    public const CSV = 'text/csv';
    public const NDJSON = 'application/x-ndjson';
    public const OCTET_STREAM = 'application/octet-stream';
    public const PROBLEM_JSON = 'application/problem+json';

    /**
     * Extract the base content type without parameters
     */
    public static function extractBaseType(string $contentType): string
    {
        return explode(';', $contentType)[0];
    }

    /**
     * Add charset parameter to content type if not already present
     */
    public static function withCharset(string $contentType, string $charset = 'utf-8'): string
    {
        if (str_contains($contentType, 'charset=')) {
            return $contentType;
        }

        return "{$contentType}; charset={$charset}";
    }

    /**
     * Determine content type for a file path based on extension
     */
    public static function fromFilePath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match($extension) {
            'json' => self::JSON,
            'txt' => self::TEXT,
            'html', 'htm' => self::HTML,
            'xml' => self::XML,
            'csv' => self::CSV,
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'zip' => 'application/zip',
            default => self::OCTET_STREAM,
        };
    }
}
