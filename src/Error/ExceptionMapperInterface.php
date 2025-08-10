<?php

declare(strict_types=1);

namespace LaqueResponses\Error;

/**
 * Interface for mapping exceptions to problem details
 */
interface ExceptionMapperInterface
{
    /**
     * Map an exception to problem details array
     * 
     * @param \Throwable $e The exception to map
     * @param bool $debug Whether to include debug information
     * @return array{type:string,title:string,status:int,detail:?string,extensions:array<string,mixed>}
     */
    public function map(\Throwable $e, bool $debug = false): array;
}