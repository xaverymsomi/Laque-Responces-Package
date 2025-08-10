<?php

declare(strict_types=1);

namespace LaqueResponses\Support;

/**
 * Array utility functions
 */
final class ArrayUtils
{
    /**
     * Convert an object to an array recursively
     * 
     * @param mixed $data The data to convert
     * @return array|scalar|null
     */
    public static function objectToArray(mixed $data): array|int|float|string|bool|null
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        
        if (is_array($data)) {
            return array_map([self::class, 'objectToArray'], $data);
        }
        
        return $data;
    }

    /**
     * Get a value from an array using dot notation
     * 
     * @param array<string, mixed> $array
     * @param string $key Dot notation key (e.g. 'user.address.city')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            
            $array = $array[$segment];
        }
        
        return $array;
    }

    /**
     * Determine if an array is associative (has string keys)
     * 
     * @param array<mixed> $array
     */
    public static function isAssociative(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }
}