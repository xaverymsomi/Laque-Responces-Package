<?php

declare(strict_types=1);

namespace Builder;

use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Contracts\ResponseFormatterInterface;
use LaqueResponses\Registry\FormatterRegistry;
use LaqueResponses\Support\ContentType;
use LaqueResponses\Support\Headers;
use LaqueResponses\Support\Status;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class ResponseBuilderTest_bkp extends TestCase
{
    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private FormatterRegistry $registry;

    private ResponseBuilder $builder;

    private ResponseFormatterInterface $jsonFormatter;

    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->registry = $this->createMock(FormatterRegistry::class);
        $this->jsonFormatter = $this->createMock(ResponseFormatterInterface::class);

        $this->builder = new ResponseBuilder(
            $this->responseFactory,
            $this->streamFactory,
            $this->registry
        );
    }

    public function testSuccessResponse(): void
    {
        // Setup mocks
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $formattedData = '{"status":"success","data":{"key":"value"}}';
        $payload = ['key' => 'value'];

        // Response factory expectations
        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(Status::OK)
            ->willReturn($response);

        // Stream factory expectations
        $this->streamFactory->expects($this->once())
            ->method('createStream')
            ->with($formattedData)
            ->willReturn($stream);

        // Response expectations
        $response->expects($this->once())
            ->method('withBody')
            ->with($stream)
            ->willReturnSelf();

        $response->expects($this->exactly(2))
            ->method('withHeader')
            ->withConsecutive(
                [Headers::CONTENT_TYPE, ContentType::JSON],
                [Headers::CACHE_CONTROL, 'no-store']
            )
            ->willReturnSelf();

        // Registry expectations
        $this->registry->expects($this->once())
            ->method('get')
            ->with(ContentType::JSON)
            ->willReturn($this->jsonFormatter);

        // Formatter expectations
        $this->jsonFormatter->expects($this->once())
            ->method('contentType')
            ->willReturn(ContentType::JSON);

        $this->jsonFormatter->expects($this->once())
            ->method('format')
            ->with($this->callback(function ($arg) use ($payload) {
                return is_array($arg) &&
                    $arg['status'] === 'success' &&
                    $arg['data'] === $payload;
            }))
            ->willReturn($formattedData);

        // Execute
        $result = $this->builder->success($payload);

        // Assert
        $this->assertSame($response, $result);
    }

    public function testErrorResponse(): void
    {
        // Setup mocks
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $formattedData = '{"status":"error","message":"Error message","errors":{"field":["Invalid"]}}';

        // Response factory expectations
        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(Status::BAD_REQUEST)
            ->willReturn($response);

        // Stream factory expectations
        $this->streamFactory->expects($this->once())
            ->method('createStream')
            ->with($formattedData)
            ->willReturn($stream);

        // Response expectations
        $response->expects($this->once())
            ->method('withBody')
            ->with($stream)
            ->willReturnSelf();

        $response->expects($this->exactly(2))
            ->method('withHeader')
            ->withConsecutive(
                [Headers::CONTENT_TYPE, ContentType::JSON],
                [Headers::CACHE_CONTROL, 'no-store']
            )
            ->willReturnSelf();

        // Registry expectations
        $this->registry->expects($this->once())
            ->method('get')
            ->with(ContentType::JSON)
            ->willReturn($this->jsonFormatter);

        // Formatter expectations
        $this->jsonFormatter->expects($this->once())
            ->method('contentType')
            ->willReturn(ContentType::JSON);

        $this->jsonFormatter->expects($this->once())
            ->method('format')
            ->with($this->callback(function ($arg) {
                return is_array($arg) &&
                    $arg['status'] === 'error' &&
                    $arg['message'] === 'Error message' &&
                    isset($arg['errors']['field']) &&
                    $arg['errors']['field'][0] === 'Invalid';
            }))
            ->willReturn($formattedData);

        // Execute
        $result = $this->builder->error('Error message', Status::BAD_REQUEST, ['field' => ['Invalid']]);

        // Assert
        $this->assertSame($response, $result);
    }

    public function testPaginatedResponse(): void
    {
        // Setup mocks
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $formattedData = '{"status":"success","meta":{"total":100,"page":2,"per_page":10,"pages":10},"data":[]}';

        // Response factory expectations
        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->with(Status::OK)
            ->willReturn($response);

        // Stream factory expectations
        $this->streamFactory->expects($this->once())
            ->method('createStream')
            ->with($formattedData)
            ->willReturn($stream);

        // Response expectations
        $response->expects($this->once())
            ->method('withBody')
            ->with($stream)
            ->willReturnSelf();

        $response->expects($this->exactly(2))
            ->method('withHeader')
            ->willReturnSelf();

        // Registry expectations
        $this->registry->expects($this->once())
            ->method('get')
            ->with(ContentType::JSON)
            ->willReturn($this->jsonFormatter);

        // Formatter expectations
        $this->jsonFormatter->expects($this->once())
            ->method('contentType')
            ->willReturn(ContentType::JSON);

        $this->jsonFormatter->expects($this->once())
            ->method('format')
            ->with($this->callback(function ($arg) {
                return is_array($arg) &&
                    $arg['status'] === 'success' &&
                    $arg['meta']['total'] === 100 &&
                    $arg['meta']['page'] === 2 &&
                    $arg['meta']['per_page'] === 10 &&
                    $arg['meta']['pages'] === 10;
            }))
            ->willReturn($formattedData);

        // Execute
        $result = $this->builder->paginated([], 100, 2, 10);

        // Assert
        $this->assertSame($response, $result);
    }
}
