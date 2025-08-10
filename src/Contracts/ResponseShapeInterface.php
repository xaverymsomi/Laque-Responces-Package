<?php

declare(strict_types=1);

namespace LaqueResponses\Contracts;

/**
 * Interface for standardizing response shapes/envelopes
 */
interface ResponseShapeInterface
{
    /**
     * Shapes the given payload into a standardized format
     * 
     * @param mixed $payload The payload to shape
     * @return array<string, mixed> The shaped response
     */
    public function shape(mixed $payload): array;
}