<?php

declare(strict_types=1);

namespace LaqueResponses\Support;

/**
 * Header constants and utilities
 */
final class Headers
{
    // Common headers
    public const CONTENT_TYPE = 'Content-Type';
    public const CONTENT_LENGTH = 'Content-Length';
    public const CONTENT_DISPOSITION = 'Content-Disposition';
    public const CACHE_CONTROL = 'Cache-Control';
    public const LOCATION = 'Location';
    public const ACCEPT = 'Accept';
    public const ACCEPT_LANGUAGE = 'Accept-Language';

    // CORS headers
    public const ACCESS_CONTROL_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
    public const ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    public const ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';
    public const ACCESS_CONTROL_EXPOSE_HEADERS = 'Access-Control-Expose-Headers';
    public const ACCESS_CONTROL_MAX_AGE = 'Access-Control-Max-Age';
    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';

    /**
     * Create a Content-Disposition header value for file download
     * Implements RFC 6266 for safe file name handling
     */
    public static function contentDisposition(string $filename, bool $inline = false): string
    {
        $disposition = $inline ? 'inline' : 'attachment';
        $asciiFilename = preg_replace('/[^\x20-\x7E]/', '_', $filename);
        
        if ($asciiFilename === $filename) {
            return "{$disposition}; filename=\"{$filename}\"";
        }
        
        // For filenames with non-ASCII characters, provide both forms
        $encodedFilename = rawurlencode($filename);
        return "{$disposition}; filename=\"{$asciiFilename}\"; filename*=UTF-8''{$encodedFilename}";
    }

    /**
     * Validate if a header value is safe (no CR/LF)
     */
    public static function isSafe(string $value): bool
    {
        return !preg_match('/[\r\n]/', $value);
    }

    /**
     * Create cache control header for common scenarios
     */
    public static function cacheControl(
        ?int $maxAge = null,
        bool $public = false,
        bool $noStore = false,
        bool $mustRevalidate = false
    ): string {
        if ($noStore) {
            return 'no-store';
        }
        
        $directives = [];
        
        if ($public) {
            $directives[] = 'public';
        } else {
            $directives[] = 'private';
        }
        
        if ($maxAge !== null) {
            $directives[] = "max-age={$maxAge}";
        }
        
        if ($mustRevalidate) {
            $directives[] = 'must-revalidate';
        }
        
        return implode(', ', $directives);
    }
}