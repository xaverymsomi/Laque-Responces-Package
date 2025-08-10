<?php

declare(strict_types=1);

namespace LaqueResponses\Builder;

use LaqueResponses\Support\ContentType;
use LaqueResponses\Support\Headers;
use LaqueResponses\Support\Status;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Builder for streaming responses and file downloads
 */
final class StreamResponseBuilder
{
    /**
     * @param ResponseFactoryInterface $responseFactory PSR-17 response factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    /**
     * Create a streaming response
     * 
     * @param callable $writer Function that writes to the stream
     * @param int $status HTTP status code
     * @param string $contentType Content type
     * @param array<string,string|array<string>> $headers Additional headers
     * @return ResponseInterface The PSR-7 response
     */
    public function stream(
        callable $writer,
        int $status = Status::OK,
        string $contentType = ContentType::OCTET_STREAM,
        array $headers = []
    ): ResponseInterface {
        // Create empty stream
        $body = $this->streamFactory->createStream();
        
        // Write content to stream using the provided callback
        $writer($body);
        
        // Rewind stream for reading
        if ($body->isSeekable()) {
            $body->rewind();
        }
        
        // Create response with the stream
        $response = $this->responseFactory->createResponse($status)
            ->withBody($body)
            ->withHeader(Headers::CONTENT_TYPE, $contentType);
        
        // Add all other headers
        foreach ($headers as $name => $value) {
            if ($name === Headers::CONTENT_TYPE) {
                continue; // Already set
            }
            
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (Headers::isSafe($v)) {
                        $response = $response->withAddedHeader($name, $v);
                    }
                }
            } elseif (Headers::isSafe($value)) {
                $response = $response->withHeader($name, $value);
            }
        }
        
        return $response;
    }

    /**
     * Send a file as a response
     * 
     * @param string $path Path to the file
     * @param string|null $downloadName Name for the download, null to use original name
     * @param string|null $contentType Content type, null to detect from file extension
     * @param bool $asAttachment Whether to force download as attachment
     * @return ResponseInterface The PSR-7 response
     * @throws RuntimeException If file not found or not readable
     */
    public function file(
        string $path,
        ?string $downloadName = null,
        ?string $contentType = null,
        bool $asAttachment = true
    ): ResponseInterface {
        // Check if file exists and is readable
        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException("File not found or not readable: {$path}");
        }
        
        // Determine content type if not provided
        $effectiveContentType = $contentType ?? ContentType::fromFilePath($path);
        
        // Determine download name
        $fileName = $downloadName ?? basename($path);
        
        // Create stream from file
        $stream = $this->streamFactory->createStreamFromFile($path);
        
        // Set headers for file download
        $headers = [];
        
        // Set Content-Disposition header
        $headers[Headers::CONTENT_DISPOSITION] = Headers::contentDisposition($fileName, !$asAttachment);
        
        // Set Content-Length if available
        $fileSize = filesize($path);
        if ($fileSize !== false) {
            $headers[Headers::CONTENT_LENGTH] = (string) $fileSize;
        }
        
        // Create response
        $response = $this->responseFactory->createResponse(Status::OK)
            ->withBody($stream)
            ->withHeader(Headers::CONTENT_TYPE, $effectiveContentType);
        
        // Add all other headers
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
}