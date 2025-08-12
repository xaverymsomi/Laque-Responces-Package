<?php

declare(strict_types=1);

namespace LaqueResponses\Error;

use LaqueResponses\Support\Status;

/**
 * Default implementation of ExceptionMapperInterface
 */
final class DefaultExceptionMapper implements ExceptionMapperInterface
{
    /**
     * Base URI for problem types
     */
    private string $baseTypeUri;

    /**
     * @param string $baseTypeUri Base URI for problem types (e.g., 'https://problems.example.com/')
     */
    public function __construct(string $baseTypeUri = 'https://problem/')
    {
        $this->baseTypeUri = rtrim($baseTypeUri, '/') . '/';
    }

    /**
     * {@inheritDoc}
     */
    public function map(\Throwable $e, bool $debug = false): array
    {
        // Standard mapping based on exception class
        $mapping = match (true) {
            $e instanceof \InvalidArgumentException,
            $e instanceof \DomainException => [
                'type' => $this->baseTypeUri . 'domain-error',
                'title' => 'Domain Error',
                'status' => Status::BAD_REQUEST,
                'extensions' => [],
            ],

            // Authentication exception (framework-specific names commented)
            // $e instanceof \AuthenticationException
            $e instanceof \RuntimeException && str_contains(get_class($e), 'Auth') => [
                'type' => $this->baseTypeUri . 'auth-required',
                'title' => 'Authentication Required',
                'status' => Status::UNAUTHORIZED,
                'extensions' => [],
            ],

            // Authorization exception
            // $e instanceof \AuthorizationException
            $e instanceof \RuntimeException && str_contains(get_class($e), 'Authorization') => [
                'type' => $this->baseTypeUri . 'not-allowed',
                'title' => 'Not Allowed',
                'status' => Status::FORBIDDEN,
                'extensions' => [],
            ],

            // Not found exception
            // $e instanceof \ResourceNotFoundException
            $e instanceof \RuntimeException && str_contains(get_class($e), 'NotFound') => [
                'type' => $this->baseTypeUri . 'not-found',
                'title' => 'Not Found',
                'status' => Status::NOT_FOUND,
                'extensions' => [],
            ],

            // Validation exception
            // $e instanceof \ValidationException
            $e instanceof \RuntimeException && str_contains(get_class($e), 'Validation') => [
                'type' => $this->baseTypeUri . 'validation-error',
                'title' => 'Validation Failed',
                'status' => Status::UNPROCESSABLE_ENTITY,
                'extensions' => $this->extractValidationErrors($e),
            ],

            // Default case for unknown exceptions
            default => [
                'type' => 'about:blank',
                'title' => 'Internal Server Error',
                'status' => Status::INTERNAL_SERVER_ERROR,
                'extensions' => [],
            ]
        };

        // Include details only in debug mode for 500 errors
        if ($mapping['status'] >= 500 && ! $debug) {
            $detail = null;
        } else {
            $detail = $e->getMessage();
        }

        // Add stack trace in debug mode
        if ($debug) {
            $mapping['extensions']['trace'] = $this->formatTrace($e);
        }

        // Add a unique error reference ID
        $mapping['extensions']['error_ref'] = $this->generateErrorRef();

        return [
            'type' => $mapping['type'],
            'title' => $mapping['title'],
            'status' => $mapping['status'],
            'detail' => $detail,
            'extensions' => $mapping['extensions'],
        ];
    }

    /**
     * Format exception trace for human readability
     */
    private function formatTrace(\Throwable $e): array
    {
        return [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15), // Limit trace lines
        ];
    }

    /**
     * Generate a unique error reference ID
     */
    private function generateErrorRef(): string
    {
        return bin2hex(random_bytes(8)); // 16 character hex string
    }

    /**
     * Extract validation errors from exception
     */
    private function extractValidationErrors(\Throwable $e): array
    {
        // Try to get validation errors from common validation exception formats
        $errors = [];

        // Check for ->errors() method on validation exceptions
        if (method_exists($e, 'errors')) {
            $errors = $e->errors();
        }

        // Check for ->getErrors() method on validation exceptions
        if (empty($errors) && method_exists($e, 'getErrors')) {
            $errors = $e->getErrors();
        }

        return ['errors' => $errors];
    }
}
