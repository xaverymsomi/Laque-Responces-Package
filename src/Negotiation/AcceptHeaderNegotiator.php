<?php

declare(strict_types=1);

namespace LaqueResponses\Negotiation;

use LaqueResponses\Registry\FormatterRegistry;

/**
 * Content negotiation based on Accept header
 */
final class AcceptHeaderNegotiator
{
    /**
     * Negotiate the best content type based on Accept header and available formatters
     *
     * @param string $acceptHeader Raw Accept header from the request
     * @param FormatterRegistry $registry Registry of available formatters
     * @return string|null The best matching content type or null if no match
     */
    public function negotiate(string $acceptHeader, FormatterRegistry $registry): ?string
    {
        if (empty($acceptHeader)) {
            return null;
        }

        // Parse Accept header into media types with quality values
        $acceptedTypes = $this->parseAcceptHeader($acceptHeader);

        // Get supported content types
        $supportedTypes = $registry->supported();

        // If client accepts anything (*/*) with highest priority
        if (isset($acceptedTypes['*/*']) && $acceptedTypes['*/*'] === 1.0) {
            return reset($supportedTypes) ?: null;
        }

        // Find best match based on quality values
        $bestType = null;
        $bestQuality = -1;

        foreach ($acceptedTypes as $type => $quality) {
            // Skip if quality is 0 (explicitly not accepted)
            if ($quality === 0.0) {
                continue;
            }

            // Handle wildcards like image/*
            if (str_ends_with($type, '/*')) {
                $baseType = substr($type, 0, -2);

                foreach ($supportedTypes as $supportedType) {
                    $supportedBaseType = explode('/', $supportedType)[0];

                    if ($baseType === '*' || $supportedBaseType === $baseType) {
                        if ($quality > $bestQuality) {
                            $bestQuality = $quality;
                            $bestType = $supportedType;
                        }
                    }
                }

                continue;
            }

            // Check for exact match
            if (in_array($type, $supportedTypes, true) && $quality > $bestQuality) {
                $bestQuality = $quality;
                $bestType = $type;
            }
        }

        return $bestType;
    }

    /**
     * Parse Accept header into media types with quality values
     *
     * @param string $acceptHeader Raw Accept header
     * @return array<string, float> Array of media types with quality values
     */
    private function parseAcceptHeader(string $acceptHeader): array
    {
        $types = [];

        // Split by comma
        $parts = explode(',', $acceptHeader);

        foreach ($parts as $part) {
            $part = trim($part);

            // Default quality value is 1.0
            $quality = 1.0;

            // Check for q parameter
            if (str_contains($part, ';')) {
                $segments = explode(';', $part);
                $mediaType = trim($segments[0]);

                // Look for q=value in parameters
                foreach (array_slice($segments, 1) as $param) {
                    if (preg_match('/^q=([0-9]*\.?[0-9]+)$/', trim($param), $matches)) {
                        $quality = (float) $matches[1];
                        // Quality values are limited to 0.0-1.0
                        $quality = max(0.0, min(1.0, $quality));

                        break;
                    }
                }
            } else {
                $mediaType = $part;
            }

            if (! empty($mediaType)) {
                $types[$mediaType] = $quality;
            }
        }

        // Sort by quality value (highest first)
        arsort($types);

        return $types;
    }
}
