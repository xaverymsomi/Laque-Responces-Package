<?php

declare(strict_types=1);

namespace LaqueResponses\Middleware;

use LaqueResponses\Negotiation\AcceptHeaderNegotiator;
use LaqueResponses\Registry\FormatterRegistry;
use LaqueResponses\Support\Headers;
use LaqueResponses\Support\Status;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware for content negotiation
 */
final class ContentNegotiationMiddleware implements MiddlewareInterface
{
    /**
     * @param AcceptHeaderNegotiator $negotiator
     * @param FormatterRegistry $registry
     * @param ResponseFactoryInterface $responseFactory
     * @param string $requestAttributeName Attribute name to store negotiated content type
     * @param bool $strict406 Whether to return 406 Not Acceptable if no match
     * @param string $defaultContentType Default content type if no match and not strict
     */
    public function __construct(
        private AcceptHeaderNegotiator $negotiator,
        private FormatterRegistry $registry,
        private ResponseFactoryInterface $responseFactory,
        private string $requestAttributeName = 'response.contentType',
        private bool $strict406 = false,
        private string $defaultContentType = 'application/json'
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Get Accept header from request
        $acceptHeader = $request->getHeaderLine(Headers::ACCEPT);
        
        // If no Accept header, use default and forward
        if (empty($acceptHeader)) {
            return $handler->handle(
                $request->withAttribute($this->requestAttributeName, $this->defaultContentType)
            );
        }
        
        // Negotiate content type
        $contentType = $this->negotiator->negotiate($acceptHeader, $this->registry);
        
        // If no match and strict mode, return 406 Not Acceptable
        if ($contentType === null && $this->strict406) {
            return $this->responseFactory
                ->createResponse(Status::NOT_ACCEPTABLE)
                ->withHeader(Headers::CONTENT_TYPE, 'text/plain')
                ->withBody($this->responseFactory->createStream(
                    'Not Acceptable: Supported content types: ' . implode(', ', $this->registry->supported())
                ));
        }
        
        // Use default content type if no match
        $effectiveContentType = $contentType ?? $this->defaultContentType;
        
        // Add negotiated content type as request attribute and forward
        return $handler->handle(
            $request->withAttribute($this->requestAttributeName, $effectiveContentType)
        );
    }
}