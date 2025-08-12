<?php

declare(strict_types=1);

namespace LaqueResponses\Middleware;

use LaqueResponses\Error\ProblemDetailsFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * PSR-15 middleware that converts exceptions to Problem Details responses (RFC 9457)
 */
final class ExceptionToProblemMiddleware implements MiddlewareInterface
{
    /**
     * @param ProblemDetailsFactory $problemFactory
     */
    public function __construct(
        private ProblemDetailsFactory $problemFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            // Use the request URI as the problem instance URI
            $instance = $request->getUri()->__toString();

            // Create a problem details response
            return $this->problemFactory->from($e, $instance);
        }
    }
}
